<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\FinancialInstitution\Models\FinancialInstitutionPartner;
use Closure;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticates BaaS partner API requests via client credentials.
 *
 * Validates X-Partner-Client-Id and X-Partner-Client-Secret headers,
 * checks partner status/IP allowlist, and binds the partner to the request.
 */
class PartnerAuthMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $clientId = $request->header(
            config('baas.partner_api.authentication.header_client_id', 'X-Partner-Client-Id'),
        );
        $clientSecret = $request->header(
            config('baas.partner_api.authentication.header_client_secret', 'X-Partner-Client-Secret'),
        );

        if (empty($clientId) || empty($clientSecret)) {
            return $this->unauthorizedResponse('Missing partner credentials.');
        }

        $partner = FinancialInstitutionPartner::where('api_client_id', $clientId)->first();

        if (! $partner) {
            Log::warning('Partner auth failed: invalid client ID', [
                'client_id' => $clientId,
                'ip'        => $request->ip(),
            ]);

            return $this->unauthorizedResponse('Invalid partner credentials.');
        }

        // Verify the client secret
        try {
            if ($clientSecret !== $partner->getApiClientSecret()) {
                Log::warning('Partner auth failed: invalid client secret', [
                    'partner_id' => $partner->id,
                    'ip'         => $request->ip(),
                ]);

                return $this->unauthorizedResponse('Invalid partner credentials.');
            }
        } catch (Exception $e) {
            Log::error('Partner auth failed: decryption error', [
                'partner_id' => $partner->id,
                'error'      => $e->getMessage(),
            ]);

            return $this->unauthorizedResponse('Invalid partner credentials.');
        }

        // Check partner status
        if (! $partner->isActive()) {
            Log::warning('Partner auth failed: partner not active', [
                'partner_id' => $partner->id,
                'status'     => $partner->status,
            ]);

            return $this->forbiddenResponse('Partner account is not active.');
        }

        // Check IP allowlist (skip if IP cannot be determined)
        $ip = $request->ip();
        if ($ip !== null && ! $partner->isIpAllowed($ip)) {
            Log::warning('Partner auth failed: IP not allowed', [
                'partner_id' => $partner->id,
                'ip'         => $ip,
            ]);

            return $this->forbiddenResponse('IP address not allowed.');
        }

        // Bind the partner to the request for downstream use
        $request->attributes->set('partner', $partner);

        return $next($request);
    }

    private function unauthorizedResponse(string $message): JsonResponse
    {
        return response()->json([
            'error'   => 'Unauthorized',
            'message' => $message,
        ], 401);
    }

    private function forbiddenResponse(string $message): JsonResponse
    {
        return response()->json([
            'error'   => 'Forbidden',
            'message' => $message,
        ], 403);
    }
}
