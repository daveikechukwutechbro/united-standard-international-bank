<?php

declare(strict_types=1);

use App\Domain\OpenBanking\Services\ConsentEnforcementService;
use App\Http\Middleware\EnforceConsent;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

uses(Tests\TestCase::class);

describe('EnforceConsent', function () {
    it('class exists', function () {
        expect(class_exists(EnforceConsent::class))->toBeTrue();
    });

    it('has handle method', function () {
        $reflection = new ReflectionClass(EnforceConsent::class);
        expect($reflection->hasMethod('handle'))->toBeTrue();
    });

    it('handle method accepts request, closure, and optional permission parameters', function () {
        $reflection = new ReflectionClass(EnforceConsent::class);
        $method = $reflection->getMethod('handle');
        $params = $method->getParameters();

        expect($params)->toHaveCount(3);
        expect($params[0]->getName())->toBe('request');
        expect($params[1]->getName())->toBe('next');
        expect($params[2]->getName())->toBe('permission');
    });

    it('permission parameter has default value', function () {
        $reflection = new ReflectionClass(EnforceConsent::class);
        $method = $reflection->getMethod('handle');
        $params = $method->getParameters();
        $permissionParam = $params[2];

        expect($permissionParam->isOptional())->toBeTrue();
        expect($permissionParam->getDefaultValue())->toBe('ReadAccountsBasic');
    });

    it('constructor requires ConsentEnforcementService', function () {
        $reflection = new ReflectionClass(EnforceConsent::class);
        $constructor = $reflection->getConstructor();

        expect($constructor)->not->toBeNull();
        assert($constructor instanceof ReflectionMethod);
        $params = $constructor->getParameters();
        expect($params)->toHaveCount(1);
        $paramType = $params[0]->getType();
        assert($paramType instanceof ReflectionNamedType);
        expect($paramType->getName())->toBe(ConsentEnforcementService::class);
    });

    it('returns 403 when X-Consent-ID header is missing', function () {
        /** @var ConsentEnforcementService $enforcement */
        $enforcement = $this->app->make(ConsentEnforcementService::class);
        $middleware = new EnforceConsent($enforcement);
        $request = Request::create('/api/v1/open-banking/accounts', 'GET');

        $response = $middleware->handle($request, fn () => new Response('ok'));

        expect($response->getStatusCode())->toBe(403);
        $body = json_decode((string) $response->getContent(), true);
        expect($body['error']['code'])->toBe('CONSENT_MISSING');
    });

    it('returns 401 when tpp_id attribute is missing', function () {
        /** @var ConsentEnforcementService $enforcement */
        $enforcement = $this->app->make(ConsentEnforcementService::class);
        $middleware = new EnforceConsent($enforcement);
        $request = Request::create('/api/v1/open-banking/accounts', 'GET');
        $request->headers->set('X-Consent-ID', 'some-consent-id');
        // tpp_id not set in attributes, user not authenticated

        $response = $middleware->handle($request, fn () => new Response('ok'));

        expect($response->getStatusCode())->toBe(401);
        $body = json_decode((string) $response->getContent(), true);
        expect($body['error']['code'])->toBe('UNAUTHORIZED');
    });
});
