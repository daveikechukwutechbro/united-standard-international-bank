<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Adapters;

use App\Domain\Compliance\Contracts\SanctionsScreeningInterface;
use Exception;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Chainalysis Sanctions Screening API adapter.
 *
 * Integrates with Chainalysis Sanctions Screening API v2 to screen
 * individuals by name and blockchain addresses against global sanctions lists.
 *
 * @see https://docs.chainalysis.com/api/sanctions-screening/
 */
class ChainalysisAdapter implements SanctionsScreeningInterface
{
    private string $apiKey;

    private string $baseUrl;

    private int $timeout;

    private int $retryAttempts;

    /**
     * @param  string  $apiKey        Chainalysis API key.
     * @param  string  $baseUrl       Base URL for the Chainalysis API.
     * @param  int     $timeout       HTTP request timeout in seconds.
     * @param  int     $retryAttempts Number of retry attempts for failed requests.
     */
    public function __construct(
        string $apiKey,
        string $baseUrl = 'https://api.chainalysis.com/api/sanctions/v2',
        int $timeout = 30,
        int $retryAttempts = 3
    ) {
        $this->apiKey = $apiKey;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;
        $this->retryAttempts = $retryAttempts;
    }

    /**
     * {@inheritDoc}
     */
    public function screenIndividual(array $searchParams): array
    {
        $results = [
            'matches'       => [],
            'lists_checked' => [],
            'total_matches' => 0,
        ];

        $name = $searchParams['name'] ?? '';

        if (empty($name)) {
            Log::warning('Chainalysis: screenIndividual called without a name', [
                'params' => $searchParams,
            ]);

            return $results;
        }

        try {
            Log::info('Chainalysis: screening individual', [
                'name'   => $name,
                'params' => array_diff_key($searchParams, ['id_number' => true]),
            ]);

            $response = $this->makeGetRequest('/entities', ['q' => $name]);

            $results['lists_checked'][] = 'Chainalysis';

            if ($response->successful()) {
                $entities = $response->json() ?? [];

                foreach ($entities as $entity) {
                    $matchData = $this->mapEntityToMatch($entity, $name);

                    if ($matchData !== null) {
                        $results['matches']['Chainalysis'][] = $matchData;
                        $results['total_matches']++;
                    }
                }

                Log::info('Chainalysis: individual screening completed', [
                    'name'          => $name,
                    'total_matches' => $results['total_matches'],
                ]);
            } else {
                Log::error('Chainalysis: entity screening failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                    'name'   => $name,
                ]);
            }
        } catch (RequestException $e) {
            Log::error('Chainalysis: HTTP request failed for individual screening', [
                'name'   => $name,
                'error'  => $e->getMessage(),
                'status' => $e->response->status(),
            ]);
        } catch (Exception $e) {
            Log::error('Chainalysis: unexpected error during individual screening', [
                'name'  => $name,
                'error' => $e->getMessage(),
            ]);
        }

        return $results;
    }

    /**
     * {@inheritDoc}
     */
    public function screenAddress(string $address, string $chain): array
    {
        $results = [
            'matches'       => [],
            'lists_checked' => [],
            'total_matches' => 0,
        ];

        if (empty($address)) {
            Log::warning('Chainalysis: screenAddress called with empty address');

            return $results;
        }

        try {
            Log::info('Chainalysis: screening blockchain address', [
                'address' => $address,
                'chain'   => $chain,
            ]);

            // Step 1: Register address for screening
            $registerResponse = $this->makePostRequest("/addresses/{$address}", [
                'address' => $address,
                'chain'   => $chain,
            ]);

            if (! $registerResponse->successful() && $registerResponse->status() !== 409) {
                // 409 means already registered, which is acceptable
                Log::error('Chainalysis: address registration failed', [
                    'status'  => $registerResponse->status(),
                    'body'    => $registerResponse->body(),
                    'address' => $address,
                ]);

                $results['lists_checked'][] = 'Chainalysis';

                return $results;
            }

            // Step 2: Retrieve screening results for the address
            $checkResponse = $this->makeGetRequest("/addresses/{$address}");

            $results['lists_checked'][] = 'Chainalysis';

            if ($checkResponse->successful()) {
                $addressData = $checkResponse->json() ?? [];

                $identifications = $addressData['identifications'] ?? [];

                foreach ($identifications as $identification) {
                    $matchData = $this->mapAddressIdentificationToMatch(
                        $identification,
                        $address,
                        $chain
                    );

                    if ($matchData !== null) {
                        $results['matches']['Chainalysis'][] = $matchData;
                        $results['total_matches']++;
                    }
                }

                Log::info('Chainalysis: address screening completed', [
                    'address'       => $address,
                    'chain'         => $chain,
                    'total_matches' => $results['total_matches'],
                ]);
            } else {
                Log::error('Chainalysis: address check failed', [
                    'status'  => $checkResponse->status(),
                    'body'    => $checkResponse->body(),
                    'address' => $address,
                ]);
            }
        } catch (RequestException $e) {
            Log::error('Chainalysis: HTTP request failed for address screening', [
                'address' => $address,
                'chain'   => $chain,
                'error'   => $e->getMessage(),
                'status'  => $e->response->status(),
            ]);
        } catch (Exception $e) {
            Log::error('Chainalysis: unexpected error during address screening', [
                'address' => $address,
                'chain'   => $chain,
                'error'   => $e->getMessage(),
            ]);
        }

        return $results;
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'Chainalysis';
    }

    /**
     * Map a Chainalysis entity response to our standard match format.
     *
     * @param  array<string, mixed>  $entity  Raw Chainalysis entity data.
     * @param  string                $queryName The name that was queried.
     * @return array<string, mixed>|null  Mapped match or null if not a valid match.
     */
    private function mapEntityToMatch(array $entity, string $queryName): ?array
    {
        $entityName = $entity['name'] ?? $entity['full_name'] ?? '';

        if (empty($entityName)) {
            return null;
        }

        $matchScore = $this->calculateNameMatchScore($queryName, $entityName);

        $programs = [];
        $sanctions = $entity['sanctions'] ?? $entity['designations'] ?? [];
        foreach ($sanctions as $sanction) {
            $programs[] = $sanction['program'] ?? $sanction['list_name'] ?? 'Unknown';
        }

        return [
            'sdn_id'      => (string) ($entity['entity_id'] ?? $entity['id'] ?? ''),
            'name'        => $entityName,
            'match_score' => $matchScore,
            'type'        => $entity['type'] ?? 'Individual',
            'program'     => implode(', ', $programs) ?: 'Chainalysis Sanctions',
            'remarks'     => $entity['description'] ?? $entity['remarks'] ?? '',
            'source'      => 'Chainalysis',
            'entity_id'   => $entity['entity_id'] ?? $entity['id'] ?? null,
        ];
    }

    /**
     * Map a Chainalysis address identification to our standard match format.
     *
     * @param  array<string, mixed>  $identification  Raw Chainalysis identification data.
     * @param  string                $address          The blockchain address screened.
     * @param  string                $chain             The blockchain network.
     * @return array<string, mixed>|null  Mapped match or null if not a valid identification.
     */
    private function mapAddressIdentificationToMatch(
        array $identification,
        string $address,
        string $chain
    ): ?array {
        $category = $identification['category'] ?? '';
        $name = $identification['name'] ?? $identification['entity_name'] ?? '';

        if (empty($category) && empty($name)) {
            return null;
        }

        return [
            'sdn_id'      => (string) ($identification['entity_id'] ?? ''),
            'name'        => $name ?: "Address: {$address}",
            'match_score' => 100, // Address match is exact
            'type'        => 'Address',
            'program'     => $category ?: 'Chainalysis Address Screening',
            'remarks'     => sprintf(
                'Chain: %s, Address: %s, Category: %s',
                $chain,
                $address,
                $category
            ),
            'source'      => 'Chainalysis',
            'address'     => $address,
            'chain'       => $chain,
            'category'    => $category,
            'description' => $identification['description'] ?? '',
            'url'         => $identification['url'] ?? '',
        ];
    }

    /**
     * Calculate a fuzzy match score between two names.
     *
     * Uses a combination of similar_text percentage and Levenshtein distance
     * to produce a score between 0 and 100.
     *
     * @param  string  $queryName   The name that was queried.
     * @param  string  $matchedName The name returned by Chainalysis.
     * @return int  Match score (0-100).
     */
    private function calculateNameMatchScore(string $queryName, string $matchedName): int
    {
        $queryNormalized = strtolower(trim($queryName));
        $matchNormalized = strtolower(trim($matchedName));

        if ($queryNormalized === $matchNormalized) {
            return 100;
        }

        similar_text($queryNormalized, $matchNormalized, $similarPercent);

        $maxLen = max(strlen($queryNormalized), strlen($matchNormalized));
        if ($maxLen === 0) {
            return 0;
        }

        $levenshtein = levenshtein($queryNormalized, $matchNormalized);
        $levenshteinScore = max(0, 100 - (int) (($levenshtein / $maxLen) * 100));

        // Weighted average: 60% similar_text, 40% levenshtein
        return (int) round(($similarPercent * 0.6) + ($levenshteinScore * 0.4));
    }

    /**
     * Make an authenticated GET request to the Chainalysis API.
     *
     * @param  string               $path   API endpoint path (relative to base URL).
     * @param  array<string, mixed> $query  Query parameters.
     * @return Response
     *
     * @throws RequestException
     */
    private function makeGetRequest(string $path, array $query = []): Response
    {
        return Http::withHeaders([
            'Token'        => $this->apiKey,
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
        ])
            ->timeout($this->timeout)
            ->retry($this->retryAttempts, 1000, throw: false)
            ->get("{$this->baseUrl}{$path}", $query);
    }

    /**
     * Make an authenticated POST request to the Chainalysis API.
     *
     * @param  string               $path  API endpoint path (relative to base URL).
     * @param  array<string, mixed> $data  Request body data.
     * @return Response
     *
     * @throws RequestException
     */
    private function makePostRequest(string $path, array $data = []): Response
    {
        return Http::withHeaders([
            'Token'        => $this->apiKey,
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
        ])
            ->timeout($this->timeout)
            ->retry($this->retryAttempts, 1000, throw: false)
            ->post("{$this->baseUrl}{$path}", $data);
    }
}
