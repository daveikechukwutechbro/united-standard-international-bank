<?php

declare(strict_types=1);

use App\Domain\Commerce\Services\MerchantOnboardingService;
use App\Http\Controllers\Api\Commerce\MobileCommerceController;
use App\Models\User;
use Illuminate\Http\Request;
use Tests\UnitTestCase;

uses(UnitTestCase::class);

beforeEach(function (): void {
    $this->merchantService = Mockery::mock(MerchantOnboardingService::class);
});

function makeCommerceController($test): MobileCommerceController
{
    return new MobileCommerceController(
        $test->merchantService,
    );
}

function commerceUserRequest(string $uri, string $method = 'GET', array $data = []): Request
{
    if ($method === 'POST') {
        $request = Request::create($uri, $method, $data, [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));
    } else {
        $request = Request::create($uri, $method, $data);
    }
    $user = Mockery::mock(User::class)->makePartial();
    $user->id = 1;
    $request->setUserResolver(fn () => $user);

    return $request;
}

// MobileCommerceController merchants() and merchantDetail() now query the database;
// see Feature/Api/Commerce/MobileCommerceDetailTest for DB-backed merchant tests.

describe('MobileCommerceController parseQr', function (): void {
    it('parses valid QR code data', function (): void {
        $controller = makeCommerceController($this);

        $request = commerceUserRequest('/api/v1/commerce/parse-qr', 'POST', [
            'qr_data' => 'finaegis://pay?merchant=merchant_001&amount=50.00&asset=USDC&network=polygon',
        ]);

        $response = $controller->parseQr($request);
        $data = $response->getData(true);

        expect($data['success'])->toBeTrue()
            ->and($data['data']['merchant_id'])->toBe('merchant_001')
            ->and($data['data']['amount'])->toBe('50.00')
            ->and($data['data']['asset'])->toBe('USDC');
    });

    it('returns 422 for invalid QR code', function (): void {
        $controller = makeCommerceController($this);

        $request = commerceUserRequest('/api/v1/commerce/parse-qr', 'POST', [
            'qr_data' => 'invalid-data',
        ]);

        $response = $controller->parseQr($request);

        expect($response->getStatusCode())->toBe(422);
    });
});

describe('MobileCommerceController createPaymentRequest', function (): void {
    it('creates a payment request', function (): void {
        $controller = makeCommerceController($this);

        $request = commerceUserRequest('/api/v1/commerce/payment-requests', 'POST', [
            'merchant_id' => 'merchant_001',
            'amount'      => '25.00',
            'asset'       => 'USDC',
            'network'     => 'polygon',
        ]);

        $response = $controller->createPaymentRequest($request);
        $data = $response->getData(true);

        expect($response->getStatusCode())->toBe(201)
            ->and($data['success'])->toBeTrue()
            ->and($data['data']['id'])->toStartWith('pr_')
            ->and($data['data']['status'])->toBe('pending');
    });
});

describe('MobileCommerceController processPayment', function (): void {
    it('processes a payment', function (): void {
        $controller = makeCommerceController($this);

        $request = commerceUserRequest('/api/v1/commerce/payments', 'POST', [
            'payment_request_id' => 'pr_test123',
        ]);

        $response = $controller->processPayment($request);
        $data = $response->getData(true);

        expect($response->getStatusCode())->toBe(201)
            ->and($data['success'])->toBeTrue()
            ->and($data['data']['id'])->toStartWith('pay_')
            ->and($data['data']['status'])->toBe('processing');
    });
});

describe('MobileCommerceController generateQr', function (): void {
    it('generates a payment QR code', function (): void {
        $controller = makeCommerceController($this);

        $request = commerceUserRequest('/api/v1/commerce/generate-qr', 'POST', [
            'amount'  => '100.00',
            'asset'   => 'USDC',
            'network' => 'polygon',
        ]);

        $response = $controller->generateQr($request);
        $data = $response->getData(true);

        expect($data['success'])->toBeTrue()
            ->and($data['data']['qr_data'])->toContain('finaegis://pay')
            ->and($data['data'])->toHaveKey('expires_at');
    });
});

describe('Commerce routes', function (): void {
    it('has commerce merchants route defined', function (): void {
        $route = app('router')->getRoutes()->getByName('mobile.commerce.merchants');
        expect($route)->not->toBeNull();
    });

    it('has commerce parse-qr route defined', function (): void {
        $route = app('router')->getRoutes()->getByName('mobile.commerce.parse-qr');
        expect($route)->not->toBeNull();
    });

    it('has commerce generate-qr route defined', function (): void {
        $route = app('router')->getRoutes()->getByName('mobile.commerce.generate-qr');
        expect($route)->not->toBeNull();
    });
});
