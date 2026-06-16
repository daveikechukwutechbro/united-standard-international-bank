<?php

declare(strict_types=1);

use Tests\TestCase;

uses(TestCase::class);

use App\Domain\Commerce\Enums\MerchantStatus;
use App\Domain\Commerce\Models\Merchant;
use App\Domain\MobilePayment\Contracts\MerchantLookupServiceInterface;
use App\Domain\MobilePayment\Enums\PaymentErrorCode;
use App\Domain\MobilePayment\Enums\PaymentIntentStatus;
use App\Domain\MobilePayment\Exceptions\MerchantNotFoundException;
use App\Domain\MobilePayment\Exceptions\PaymentIntentException;
use App\Domain\MobilePayment\Models\PaymentIntent;
use App\Domain\MobilePayment\Services\FeeEstimationService;
use App\Domain\MobilePayment\Services\PaymentIntentService;
use Illuminate\Support\Str;

describe('PaymentIntentService', function (): void {
    beforeEach(function (): void {
        $this->merchantLookup = Mockery::mock(MerchantLookupServiceInterface::class);
        $this->feeEstimation = new FeeEstimationService();
        $this->service = new PaymentIntentService($this->merchantLookup, $this->feeEstimation);

        // Create a test merchant
        $this->merchant = new Merchant();
        $this->merchant->id = (string) Str::uuid();
        $this->merchant->public_id = 'merchant_test123';
        $this->merchant->display_name = 'Test Merchant';
        $this->merchant->icon_url = 'https://example.com/icon.png';
        $this->merchant->accepted_assets = ['USDC'];
        $this->merchant->accepted_networks = ['SOLANA', 'TRON'];
        $this->merchant->status = MerchantStatus::ACTIVE;
    });

    it('throws MerchantNotFoundException when merchant is not found', function (): void {
        $this->merchantLookup->shouldReceive('findByPublicId')
            ->with('merchant_unknown')
            ->andThrow(new MerchantNotFoundException('merchant_unknown'));

        $this->service->create(1, [
            'merchantId'       => 'merchant_unknown',
            'amount'           => '12.00',
            'asset'            => 'USDC',
            'preferredNetwork' => 'SOLANA',
        ]);
    })->throws(MerchantNotFoundException::class);

    it('throws when merchant cannot accept payments', function (): void {
        $inactiveMerchant = clone $this->merchant;
        $inactiveMerchant->status = MerchantStatus::SUSPENDED;

        $this->merchantLookup->shouldReceive('findByPublicId')
            ->andReturn($inactiveMerchant);

        try {
            $this->service->create(1, [
                'merchantId'       => 'merchant_test123',
                'amount'           => '12.00',
                'asset'            => 'USDC',
                'preferredNetwork' => 'SOLANA',
            ]);
            $this->fail('Expected PaymentIntentException');
        } catch (PaymentIntentException $e) {
            expect($e->errorCode)->toBe(PaymentErrorCode::MERCHANT_UNREACHABLE);
        }
    });

    it('throws WRONG_TOKEN when merchant does not accept the asset', function (): void {
        $this->merchantLookup->shouldReceive('findByPublicId')
            ->andReturn($this->merchant);
        $this->merchantLookup->shouldReceive('acceptsPayment')
            ->andReturn(false);

        // Merchant doesn't accept the asset
        $this->merchant->accepted_assets = ['ETH'];

        try {
            $this->service->create(1, [
                'merchantId'       => 'merchant_test123',
                'amount'           => '12.00',
                'asset'            => 'USDC',
                'preferredNetwork' => 'SOLANA',
            ]);
            $this->fail('Expected PaymentIntentException');
        } catch (PaymentIntentException $e) {
            expect($e->errorCode)->toBe(PaymentErrorCode::WRONG_TOKEN);
        }
    });

    it('throws WRONG_NETWORK when merchant does not accept the network', function (): void {
        $this->merchantLookup->shouldReceive('findByPublicId')
            ->andReturn($this->merchant);
        $this->merchantLookup->shouldReceive('acceptsPayment')
            ->andReturn(false);

        // Merchant accepts USDC but not on TRON
        $this->merchant->accepted_networks = ['SOLANA'];

        try {
            $this->service->create(1, [
                'merchantId'       => 'merchant_test123',
                'amount'           => '12.00',
                'asset'            => 'USDC',
                'preferredNetwork' => 'TRON',
            ]);
            $this->fail('Expected PaymentIntentException');
        } catch (PaymentIntentException $e) {
            expect($e->errorCode)->toBe(PaymentErrorCode::WRONG_NETWORK);
        }
    });

    it('validates INTENT_ALREADY_SUBMITTED error code message', function (): void {
        $exception = new PaymentIntentException(PaymentErrorCode::INTENT_ALREADY_SUBMITTED);

        expect($exception->errorCode)->toBe(PaymentErrorCode::INTENT_ALREADY_SUBMITTED);
        expect($exception->httpStatus())->toBe(409);
        expect($exception->getMessage())->toContain('already been submitted');
    });

    it('validates INTENT_EXPIRED error code message', function (): void {
        $exception = new PaymentIntentException(PaymentErrorCode::INTENT_EXPIRED);

        expect($exception->errorCode)->toBe(PaymentErrorCode::INTENT_EXPIRED);
        expect($exception->httpStatus())->toBe(409);
        expect($exception->getMessage())->toContain('expired');
    });

    it('throws when cancelling a non-cancellable intent', function (): void {
        $intent = Mockery::mock(PaymentIntent::class)->makePartial();
        $intent->status = PaymentIntentStatus::PENDING;
        $intent->public_id = 'pi_test';

        $service = Mockery::mock(PaymentIntentService::class, [$this->merchantLookup, $this->feeEstimation])
            ->makePartial();
        $service->shouldReceive('get')
            ->with('pi_test', 1)
            ->andReturn($intent);

        try {
            $service->cancel('pi_test', 1, 'user_cancelled');
            $this->fail('Expected PaymentIntentException');
        } catch (PaymentIntentException $e) {
            expect($e->errorCode)->toBe(PaymentErrorCode::INTENT_ALREADY_SUBMITTED);
        }
    });
});

describe('FeeEstimationService', function (): void {
    it('estimates standard fees for Solana', function (): void {
        $service = new FeeEstimationService();
        $fee = $service->estimate(App\Domain\MobilePayment\Enums\PaymentNetwork::SOLANA, '100.00', false);

        expect($fee->nativeAsset)->toBe('SOL');
        expect($fee->amount)->toBe('0.00004');
        expect($fee->usdApprox)->toBe('0.01');
    });

    it('estimates standard fees for Tron', function (): void {
        $service = new FeeEstimationService();
        $fee = $service->estimate(App\Domain\MobilePayment\Enums\PaymentNetwork::TRON, '100.00', false);

        expect($fee->nativeAsset)->toBe('TRX');
        expect($fee->amount)->toBe('5.0');
        expect($fee->usdApprox)->toBe('0.50');
    });

    it('doubles fees for shield-enabled transactions', function (): void {
        $service = new FeeEstimationService();
        $fee = $service->estimate(App\Domain\MobilePayment\Enums\PaymentNetwork::SOLANA, '100.00', true);

        expect($fee->nativeAsset)->toBe('SOL');
        expect($fee->amount)->toBe('0.00008000');
        expect($fee->usdApprox)->toBe('0.02');
    });
});
