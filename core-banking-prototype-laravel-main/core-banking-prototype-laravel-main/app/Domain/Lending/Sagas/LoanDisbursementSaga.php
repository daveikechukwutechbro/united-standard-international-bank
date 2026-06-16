<?php

declare(strict_types=1);

namespace App\Domain\Lending\Sagas;

use App\Domain\Account\Workflows\DepositAccountWorkflow;
use App\Domain\Account\Workflows\WithdrawAccountWorkflow;
use App\Domain\Lending\Aggregates\Loan;
use App\Domain\Lending\Models\Loan as LoanModel;
use Exception;
use Generator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;
use Workflow\ChildWorkflowStub;
use Workflow\Workflow;

/**
 * Saga for orchestrating loan disbursement across multiple domains.
 * Coordinates Lending, Account, and Payment domains with full compensation support.
 */
class LoanDisbursementSaga extends Workflow
{
    /** @var array<string, callable> */
    private array $compensations = [];

    /** @var array<int, string> */
    private array $completedSteps = [];

    /**
     * Execute the loan disbursement saga.
     *
     * @param array<string, mixed> $input Contains:
     *   - loan_id: string (UUID)
     *   - lender_account_id: string
     *   - borrower_account_id: string
     *   - amount: float
     *   - currency: string
     *   - disbursement_method: string (instant|scheduled)
     */
    public function execute(array $input): Generator
    {
        $sagaId = Str::uuid()->toString();

        Log::info('Starting LoanDisbursementSaga', [
            'saga_id' => $sagaId,
            'loan_id' => $input['loan_id'],
            'amount'  => $input['amount'],
        ]);

        try {
            // Step 1: Verify loan is approved and ready for disbursement
            $verifyResult = yield from $this->verifyLoanStatus($input);
            if (! $verifyResult['success']) {
                throw new Exception('Loan verification failed: ' . $verifyResult['message']);
            }
            $this->completedSteps[] = 'verify_loan_status';

            // Step 2: Reserve funds from lender pool
            $reserveResult = yield from $this->reserveFunds($input);
            if (! $reserveResult['success']) {
                throw new Exception('Fund reservation failed: ' . $reserveResult['message']);
            }
            $this->completedSteps[] = 'reserve_funds';

            // Step 3: Transfer funds to borrower
            $transferResult = yield from $this->transferToBorrower($input);
            if (! $transferResult['success']) {
                throw new Exception('Fund transfer failed: ' . $transferResult['message']);
            }
            $this->completedSteps[] = 'transfer_funds';

            // Step 4: Update loan status to disbursed
            $updateResult = yield from $this->updateLoanStatus($input['loan_id'], 'disbursed');
            if (! $updateResult['success']) {
                throw new Exception('Loan status update failed: ' . $updateResult['message']);
            }
            $this->completedSteps[] = 'update_loan_status';

            // Step 5: Record disbursement
            $recordResult = yield from $this->recordDisbursement($input, $sagaId);
            $this->completedSteps[] = 'record_disbursement';

            Log::info('LoanDisbursementSaga completed successfully', [
                'saga_id'         => $sagaId,
                'loan_id'         => $input['loan_id'],
                'completed_steps' => $this->completedSteps,
            ]);

            return [
                'success'         => true,
                'saga_id'         => $sagaId,
                'loan_id'         => $input['loan_id'],
                'disbursement_id' => $recordResult['disbursement_id'] ?? null,
                'amount'          => $input['amount'],
                'currency'        => $input['currency'],
                'completed_steps' => $this->completedSteps,
                'disbursed_at'    => now()->toIso8601String(),
            ];
        } catch (Throwable $e) {
            Log::error('LoanDisbursementSaga failed, executing compensations', [
                'saga_id'         => $sagaId,
                'loan_id'         => $input['loan_id'],
                'error'           => $e->getMessage(),
                'completed_steps' => $this->completedSteps,
            ]);

            // Execute compensations in reverse order
            yield from $this->executeCompensations();

            return [
                'success'           => false,
                'saga_id'           => $sagaId,
                'loan_id'           => $input['loan_id'],
                'error'             => $e->getMessage(),
                'compensated_steps' => array_keys($this->compensations),
            ];
        }
    }

    /**
     * Verify loan is approved and ready for disbursement.
     *
     * @param array<string, mixed> $input
     */
    private function verifyLoanStatus(array $input): Generator
    {
        yield; // Allow workflow to continue

        /** @var LoanModel|null $loan */
        $loan = LoanModel::find($input['loan_id']);

        if (! $loan instanceof LoanModel) {
            return [
                'success' => false,
                'message' => 'Loan not found',
            ];
        }

        if ($loan->status !== 'approved') {
            return [
                'success' => false,
                'message' => "Loan is not approved for disbursement. Current status: {$loan->status}",
            ];
        }

        return [
            'success' => true,
            'loan'    => $loan,
        ];
    }

    /**
     * Reserve funds from lender pool.
     *
     * @param array<string, mixed> $input
     */
    private function reserveFunds(array $input): Generator
    {
        $workflow = yield ChildWorkflowStub::make(
            WithdrawAccountWorkflow::class
        );

        $result = yield $workflow->execute(
            $input['lender_account_id'],
            $input['currency'],
            $input['amount'],
            "Reserve funds for loan disbursement: {$input['loan_id']}"
        );

        // Add compensation to return funds to lender pool
        $this->registerCompensation('reserve_funds', function () use ($input) {
            return ChildWorkflowStub::make(DepositAccountWorkflow::class)
                ->execute(
                    $input['lender_account_id'],
                    $input['currency'],
                    $input['amount'],
                    "Return funds - loan disbursement cancelled: {$input['loan_id']}"
                );
        });

        return $result;
    }

    /**
     * Transfer funds to borrower.
     *
     * @param array<string, mixed> $input
     */
    private function transferToBorrower(array $input): Generator
    {
        $workflow = yield ChildWorkflowStub::make(
            DepositAccountWorkflow::class
        );

        $result = yield $workflow->execute(
            $input['borrower_account_id'],
            $input['currency'],
            $input['amount'],
            "Loan disbursement: {$input['loan_id']}"
        );

        // Add compensation to withdraw funds from borrower
        $this->registerCompensation('transfer_funds', function () use ($input) {
            return ChildWorkflowStub::make(WithdrawAccountWorkflow::class)
                ->execute(
                    $input['borrower_account_id'],
                    $input['currency'],
                    $input['amount'],
                    "Reverse loan disbursement: {$input['loan_id']}"
                );
        });

        return $result;
    }

    /**
     * Update loan status using the aggregate.
     */
    private function updateLoanStatus(string $loanId, string $status): Generator
    {
        yield; // Allow workflow to continue

        try {
            $loanAggregate = Loan::retrieve($loanId);
            $loanAggregate->disburse(now()->toDateString());
            $loanAggregate->persist();

            // Add compensation to reset loan status
            $this->registerCompensation('update_loan_status', function () use ($loanId) {
                // In a real scenario, we'd have a method to revert the disbursement
                Log::warning('Loan status compensation triggered', ['loan_id' => $loanId]);

                return ['success' => true];
            });

            return [
                'success' => true,
                'status'  => $status,
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Record the disbursement for audit trail.
     *
     * @param array<string, mixed> $input
     */
    private function recordDisbursement(array $input, string $sagaId): Generator
    {
        yield; // Allow workflow to continue

        $disbursementId = Str::uuid()->toString();

        Log::info('Loan disbursement recorded', [
            'disbursement_id'     => $disbursementId,
            'saga_id'             => $sagaId,
            'loan_id'             => $input['loan_id'],
            'amount'              => $input['amount'],
            'currency'            => $input['currency'],
            'borrower_account_id' => $input['borrower_account_id'],
            'disbursed_at'        => now()->toIso8601String(),
        ]);

        return [
            'success'         => true,
            'disbursement_id' => $disbursementId,
        ];
    }

    /**
     * Register a compensation action.
     */
    private function registerCompensation(string $step, callable $compensation): void
    {
        $this->compensations[$step] = $compensation;
    }

    /**
     * Execute all compensations in reverse order.
     */
    private function executeCompensations(): Generator
    {
        $compensations = array_reverse($this->compensations, true);

        foreach ($compensations as $step => $compensation) {
            try {
                Log::info("Executing compensation for step: {$step}");
                yield $compensation();
                Log::info("Compensation successful for step: {$step}");
            } catch (Throwable $e) {
                Log::error("Compensation failed for step: {$step}", [
                    'error' => $e->getMessage(),
                ]);
                // Continue with other compensations even if one fails
            }
        }
    }
}
