<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Payment\Contracts\PaymentServiceInterface;
use App\Domain\Payment\Services\PaymentGatewayService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: 'Open Banking Deposits',
    description: 'Open banking deposit initiation and callbacks'
)]
class OpenBankingDepositController extends Controller
{
    protected PaymentGatewayService $paymentGateway;

    protected PaymentServiceInterface $paymentService;

    public function __construct(
        PaymentGatewayService $paymentGateway,
        PaymentServiceInterface $paymentService
    ) {
        $this->paymentGateway = $paymentGateway;
        $this->paymentService = $paymentService;
    }

        #[OA\Post(
            path: '/open-banking/deposits/initiate',
            operationId: 'openBankingDepositsInitiate',
            tags: ['Open Banking Deposits'],
            summary: 'Initiate open banking deposit',
            description: 'Initiates a deposit via open banking',
            security: [['sanctum' => []]]
        )]
    #[OA\Response(
        response: 201,
        description: 'Successful operation'
    )]
    #[OA\Response(
        response: 500,
        description: 'Server error'
    )]
    public function initiate(Request $request)
    {
        $request->validate([
            'amount'   => 'required|numeric|min:1|max:10000',
            'currency' => 'required|in:USD,EUR,GBP',
            'bank'     => 'required|string',
        ]);

        $user = Auth::user();
        $account = $user->accounts()->first();

        if (! $account) {
            return redirect()->route('wallet.deposit')
                ->with('error', 'Please create an account first.');
        }

        // In demo environment or sandbox mode, simulate the bank authorization
        if (app()->environment('demo') || config('demo.sandbox.enabled')) {
            Log::info('Simulating OpenBanking deposit in demo mode', [
                'user_id' => $user->id,
                'amount'  => $request->amount,
                'bank'    => $request->bank,
            ]);

            // Generate a cryptographically secure state nonce for CSRF protection
            $nonce = bin2hex(random_bytes(32));

            // Store the deposit details in cache (10-minute TTL) keyed by nonce
            Cache::put("ob_state:{$nonce}", [
                'amount'       => $request->amount * 100, // Convert to cents
                'currency'     => $request->currency,
                'bank'         => $request->bank,
                'account_uuid' => $account->uuid,
                'user_id'      => $user->id,
                'initiated_at' => now()->toIso8601String(),
            ], 600);

            // Simulate immediate callback (normally would redirect to bank with state param)
            return redirect()->route('wallet.deposit.openbanking.callback', ['state' => $nonce])
                ->with('demo_mode', true);
        }

        // OpenBanking integration not enabled
        return response()->json([
            'message' => 'Open Banking deposits are not available at this time',
            'error'   => 'SERVICE_UNAVAILABLE',
        ], 503);
    }

        #[OA\Get(
            path: '/open-banking/deposits/callback',
            operationId: 'openBankingDepositsCallback',
            tags: ['Open Banking Deposits'],
            summary: 'Open banking callback',
            description: 'Handles the open banking payment callback',
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
    public function callback(Request $request)
    {
        // In demo environment or sandbox mode, process the simulated deposit
        if (app()->environment('demo') || config('demo.sandbox.enabled')) {
            $nonce = $request->query('state', '');

            if (! $nonce || strlen($nonce) < 32) {
                return redirect()->route('wallet.deposit')
                    ->with('error', 'State expired or invalid.');
            }

            // Atomically fetch and delete the state (single-use nonce)
            $depositData = Cache::pull("ob_state:{$nonce}");

            if (! $depositData) {
                return redirect()->route('wallet.deposit')
                    ->with('error', 'State expired or invalid.');
            }

            try {
                // Process the deposit through the payment service
                $reference = 'OB-DEP-' . strtoupper(uniqid());
                $this->paymentService->processOpenBankingDeposit([
                    'account_uuid' => $depositData['account_uuid'],
                    'amount'       => $depositData['amount'],
                    'currency'     => $depositData['currency'],
                    'reference'    => $reference,
                    'bank_name'    => $depositData['bank'],
                    'metadata'     => [
                        'environment'   => app()->environment('demo') ? 'demo' : 'sandbox',
                        'processor'     => 'openbanking_simulation',
                        'bank'          => $depositData['bank'],
                        'authorized_at' => now()->toIso8601String(),
                    ],
                ]);

                Log::info('OpenBanking deposit processed successfully', [
                    'reference' => $reference,
                    'amount'    => $depositData['amount'],
                    'bank'      => $depositData['bank'],
                ]);

                return redirect()->route('wallet.index')
                    ->with('success', sprintf(
                        'OpenBanking deposit successful! %s %s has been credited from %s.',
                        $depositData['currency'],
                        number_format($depositData['amount'] / 100, 2),
                        $depositData['bank']
                    ));
            } catch (Exception $e) {
                Log::error('OpenBanking deposit failed', [
                    'error'        => $e->getMessage(),
                    'deposit_data' => $depositData,
                ]);

                return redirect()->route('wallet.deposit')
                    ->with('error', 'Failed to process OpenBanking deposit: ' . $e->getMessage());
            }
        }

        // OpenBanking integration not enabled
        return response()->json([
            'message' => 'Open Banking deposits are not available at this time',
            'error'   => 'SERVICE_UNAVAILABLE',
        ], 503);
    }
}
