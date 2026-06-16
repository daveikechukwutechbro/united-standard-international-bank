<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class GraphQLQueryCostMiddleware
{
    /**
     * Estimated cost per query operation type.
     */
    private const COST_QUERY = 1;

    private const COST_MUTATION = 5;

    private const COST_SUBSCRIPTION = 10;

    private const COST_NESTED_FIELD = 1;

    private const MAX_COST = 500;

    public function handle(Request $request, Closure $next): Response
    {
        $query = $request->input('query', '');

        if (empty($query)) {
            return $next($request);
        }

        $cost = $this->estimateCost($query);
        $maxCost = (int) config('lighthouse.query_cost.max_cost', self::MAX_COST);

        if ($cost > $maxCost) {
            Log::warning('GraphQL query cost exceeded', [
                'cost'     => $cost,
                'max_cost' => $maxCost,
                'user_id'  => $request->user()?->id,
                'ip'       => $request->ip(),
            ]);

            return new JsonResponse([
                'errors' => [
                    [
                        'message'    => "Query cost ({$cost}) exceeds maximum allowed ({$maxCost}).",
                        'extensions' => [
                            'category' => 'query_cost',
                            'cost'     => $cost,
                            'max_cost' => $maxCost,
                        ],
                    ],
                ],
            ], 400);
        }

        $response = $next($request);

        if ($response instanceof \Illuminate\Http\Response || $response instanceof JsonResponse) {
            $response->headers->set('X-GraphQL-Cost', (string) $cost);
            $response->headers->set('X-GraphQL-Max-Cost', (string) $maxCost);
        }

        return $response;
    }

    private function estimateCost(string $query): int
    {
        $cost = 0;

        // Count top-level operations
        $cost += substr_count($query, 'query') * self::COST_QUERY;
        $cost += substr_count($query, 'mutation') * self::COST_MUTATION;
        $cost += substr_count($query, 'subscription') * self::COST_SUBSCRIPTION;

        // Count field selections (opening braces indicate nested selections)
        $braceDepth = 0;
        $maxDepth = 0;
        $fieldCount = 0;

        for ($i = 0, $len = strlen($query); $i < $len; $i++) {
            if ($query[$i] === '{') {
                $braceDepth++;
                $maxDepth = max($maxDepth, $braceDepth);
            } elseif ($query[$i] === '}') {
                $braceDepth--;
            }
        }

        // Estimate fields by counting lines with alphanumeric content between braces
        $lines = preg_split('/\R/', $query) ?: [];
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (
                ! empty($trimmed) && ! str_starts_with($trimmed, '{') && ! str_starts_with($trimmed, '}')
                && ! str_starts_with($trimmed, '#') && ! str_starts_with($trimmed, 'query')
                && ! str_starts_with($trimmed, 'mutation') && ! str_starts_with($trimmed, 'subscription')
                && ! str_starts_with($trimmed, '$') && ! str_starts_with($trimmed, '...')
            ) {
                $fieldCount++;
            }
        }

        $cost += $fieldCount * self::COST_NESTED_FIELD;

        // Depth penalty (exponential cost for deeply nested queries)
        if ($maxDepth > 5) {
            $cost += (int) pow(2, $maxDepth - 5);
        }

        return max(1, $cost);
    }
}
