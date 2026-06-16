<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Banking;

use App\Domain\Banking\Exceptions\BankOperationException;
use App\Domain\Banking\Services\AccountVerificationService;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AccountVerificationController extends Controller
{
    public function __construct(
        private readonly AccountVerificationService $verificationService,
    ) {
    }

    /**
     * Initiate micro-deposit verification for an account.
     */
    public function initiateMicroDeposit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'account_id' => 'required|string',
            'iban'       => 'required|string|max:34',
            'currency'   => 'sometimes|string|size:3',
        ]);

        /** @var User $user */
        $user = $request->user();

        try {
            $result = $this->verificationService->initiateMicroDeposit(
                $user->uuid,
                $validated['account_id'],
                $validated['iban'],
                $validated['currency'] ?? 'EUR',
            );

            return response()->json([
                'data'    => $result,
                'message' => 'Micro-deposit verification initiated.',
            ], 201);
        } catch (BankOperationException $e) {
            return response()->json([
                'error'   => 'Verification failed.',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Confirm micro-deposit amounts to verify account ownership.
     */
    public function confirmMicroDeposit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'verification_id' => 'required|string',
            'amount_1'        => 'required|integer|min:1|max:99',
            'amount_2'        => 'required|integer|min:1|max:99',
        ]);

        try {
            $result = $this->verificationService->verifyMicroDeposit(
                $validated['verification_id'],
                [$validated['amount_1'], $validated['amount_2']],
            );

            return response()->json([
                'data' => $result,
            ]);
        } catch (BankOperationException $e) {
            return response()->json([
                'error'   => 'Verification failed.',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Initiate instant verification via Open Banking.
     */
    public function instantVerify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'account_id' => 'required|string',
            'iban'       => 'required|string|max:34',
        ]);

        /** @var User $user */
        $user = $request->user();

        try {
            $result = $this->verificationService->initiateInstantVerification(
                $user->uuid,
                $validated['account_id'],
                $validated['iban'],
            );

            return response()->json([
                'data'    => $result,
                'message' => 'Instant verification initiated.',
            ], 201);
        } catch (BankOperationException $e) {
            Log::warning('Instant verification failed', [
                'user_uuid' => $user->uuid,
                'error'     => $e->getMessage(),
            ]);

            return response()->json([
                'error'   => 'Verification failed.',
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
