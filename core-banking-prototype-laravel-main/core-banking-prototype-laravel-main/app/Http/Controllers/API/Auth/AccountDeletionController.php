<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

class AccountDeletionController extends Controller
{
    /**
     * Soft-delete the authenticated user's account and revoke all tokens.
     *
     * POST /auth/delete-account
     */
    #[OA\Post(
        path: '/api/auth/delete-account',
        operationId: 'accountDeletion',
        summary: 'Delete user account',
        description: 'Soft-deletes the authenticated user\'s account, revokes all tokens, and schedules the account for permanent deletion.',
        tags: ['Account Deletion'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['confirmation'], properties: [
        new OA\Property(property: 'confirmation', type: 'string', example: 'DELETE', description: 'Must be the string \'DELETE\' to confirm account deletion'),
        ]))
    )]
    #[OA\Response(
        response: 200,
        description: 'Account scheduled for deletion',
        content: new OA\JsonContent(properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'data', type: 'object', properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Account has been scheduled for deletion.'),
        new OA\Property(property: 'deleted_at', type: 'string', format: 'date-time'),
        ]),
        ])
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated'
    )]
    #[OA\Response(
        response: 422,
        description: 'Validation error — confirmation field is required and must be \'DELETE\''
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'confirmation' => ['required', 'string', 'in:DELETE'],
        ]);

        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'error'   => [
                    'code'    => 'UNAUTHENTICATED',
                    'message' => 'No authenticated user.',
                ],
            ], 401);
        }

        Log::warning('Account deletion requested', [
            'user_id' => $user->id,
            'email'   => $user->email,
            'ip'      => $request->ip(),
        ]);

        // Revoke all tokens
        $user->tokens()->delete();

        // Soft-delete the user
        $user->delete();

        return response()->json([
            'success' => true,
            'data'    => [
                'message'    => 'Account has been scheduled for deletion.',
                'deleted_at' => now()->toIso8601String(),
            ],
        ]);
    }
}
