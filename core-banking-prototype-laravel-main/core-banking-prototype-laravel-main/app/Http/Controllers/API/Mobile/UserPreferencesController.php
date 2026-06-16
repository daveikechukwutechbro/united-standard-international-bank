<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: 'Mobile User Preferences',
    description: 'Mobile application user preferences management'
)]
class UserPreferencesController extends Controller
{
    private const DEFAULTS = [
        'activeNetwork'           => 'solana',
        'isPrivacyModeEnabled'    => true,
        'autoLockEnabled'         => true,
        'transactionAuthRequired' => true,
        'hideBalances'            => false,
        'poiEnabled'              => true,
        'biometricLockEnabled'    => true,
    ];

    private const VALIDATION_RULES = [
        'activeNetwork'           => 'string|max:50',
        'isPrivacyModeEnabled'    => 'boolean',
        'autoLockEnabled'         => 'boolean',
        'transactionAuthRequired' => 'boolean',
        'hideBalances'            => 'boolean',
        'poiEnabled'              => 'boolean',
        'biometricLockEnabled'    => 'boolean',
    ];

        #[OA\Get(
            path: '/api/v1/user/preferences',
            operationId: 'mobileUserPreferencesShow',
            tags: ['Mobile User Preferences'],
            summary: 'Get user mobile preferences',
            description: 'Returns the authenticated user mobile preferences',
            security: [['sanctum' => []]]
        )]
    #[OA\Response(
        response: 200,
        description: 'Successful operation'
    )]
    #[OA\Response(
        response: 500,
        description: 'Server error'
    )]
    public function show(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        /** @var array<string, mixed> $stored */
        $stored = $user->mobile_preferences ?? [];
        $merged = array_merge(self::DEFAULTS, $stored);

        return response()->json([
            'success' => true,
            'data'    => $merged,
        ]);
    }

        #[OA\Patch(
            path: '/api/v1/user/preferences',
            operationId: 'mobileUserPreferencesUpdate',
            tags: ['Mobile User Preferences'],
            summary: 'Update user mobile preferences',
            description: 'Updates the authenticated user mobile preferences. Accepts fields at the top level or nested under a \'preferences\' key.',
            security: [['sanctum' => []]]
        )]
    #[OA\Response(
        response: 200,
        description: 'Successful operation'
    )]
    #[OA\Response(
        response: 500,
        description: 'Server error'
    )]
    public function update(Request $request): JsonResponse
    {
        // Support both flat payloads and payloads nested under 'preferences' key
        $input = $request->all();
        if (isset($input['preferences']) && is_array($input['preferences'])) {
            $input = array_merge($input, $input['preferences']);
            unset($input['preferences']);
        }

        $validator = Validator::make($input, self::VALIDATION_RULES);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error'   => [
                    'code'    => 'VALIDATION_ERROR',
                    'message' => 'Invalid preference values',
                    'details' => $validator->errors(),
                ],
            ], 422);
        }

        $allowedKeys = array_keys(self::DEFAULTS);
        $incoming = array_intersect_key($input, array_flip($allowedKeys));

        if (empty($incoming)) {
            return response()->json([
                'success' => false,
                'error'   => [
                    'code'    => 'NO_VALID_FIELDS',
                    'message' => 'No valid preference fields provided. Accepted fields: ' . implode(', ', $allowedKeys),
                ],
            ], 400);
        }

        /** @var \App\Models\User $user */
        $user = $request->user();
        /** @var array<string, mixed> $stored */
        $stored = $user->mobile_preferences ?? [];
        $updated = array_merge($stored, $incoming);

        $user->mobile_preferences = $updated; /** @phpstan-ignore assign.propertyType */
        $user->save();

        $merged = array_merge(self::DEFAULTS, $updated);

        return response()->json([
            'success' => true,
            'data'    => $merged,
        ]);
    }
}
