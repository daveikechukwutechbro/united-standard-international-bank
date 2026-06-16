<?php

declare(strict_types=1);

use App\Domain\OpenBanking\Services\TppRegistrationService;
use App\Http\Middleware\ValidateTppCertificate;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

uses(Tests\TestCase::class);

describe('ValidateTppCertificate', function () {
    it('class exists', function () {
        expect(class_exists(ValidateTppCertificate::class))->toBeTrue();
    });

    it('has handle method', function () {
        $reflection = new ReflectionClass(ValidateTppCertificate::class);
        expect($reflection->hasMethod('handle'))->toBeTrue();
    });

    it('handle method accepts request and closure parameters', function () {
        $reflection = new ReflectionClass(ValidateTppCertificate::class);
        $method = $reflection->getMethod('handle');
        $params = $method->getParameters();

        expect($params)->toHaveCount(2);
        expect($params[0]->getName())->toBe('request');
        expect($params[1]->getName())->toBe('next');
    });

    it('constructor requires TppRegistrationService', function () {
        $reflection = new ReflectionClass(ValidateTppCertificate::class);
        $constructor = $reflection->getConstructor();

        expect($constructor)->not->toBeNull();
        assert($constructor instanceof ReflectionMethod);
        $params = $constructor->getParameters();
        expect($params)->toHaveCount(1);
        $paramType = $params[0]->getType();
        assert($paramType instanceof ReflectionNamedType);
        expect($paramType->getName())->toBe(TppRegistrationService::class);
    });

    it('bypasses validation when tpp_certificate_validation is disabled', function () {
        config(['openbanking.tpp_certificate_validation' => false]);

        /** @var TppRegistrationService $service */
        $service = $this->app->make(TppRegistrationService::class);
        $middleware = new ValidateTppCertificate($service);
        $request = Request::create('/api/v1/open-banking/accounts', 'GET');

        $response = $middleware->handle($request, fn () => new Response('ok'));

        expect($response->getContent())->toBe('ok');
    });

    it('returns 403 when X-TPP-Client-ID header is missing', function () {
        config(['openbanking.tpp_certificate_validation' => true]);

        /** @var TppRegistrationService $service */
        $service = $this->app->make(TppRegistrationService::class);
        $middleware = new ValidateTppCertificate($service);
        $request = Request::create('/api/v1/open-banking/accounts', 'GET');

        $response = $middleware->handle($request, fn () => new Response('ok'));

        expect($response->getStatusCode())->toBe(403);
        $body = json_decode((string) $response->getContent(), true);
        expect($body['error']['code'])->toBe('TPP_NOT_IDENTIFIED');
    });
});
