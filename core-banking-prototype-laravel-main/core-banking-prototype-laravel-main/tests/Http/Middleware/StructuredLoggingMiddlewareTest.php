<?php

declare(strict_types=1);

use App\Http\Middleware\StructuredLoggingMiddleware;

describe('StructuredLoggingMiddleware', function () {
    it('exists and is a valid class', function () {
        expect((new ReflectionClass(StructuredLoggingMiddleware::class))->getName())->not->toBeEmpty();
    });

    it('has handle method', function () {
        expect((new ReflectionClass(StructuredLoggingMiddleware::class))->hasMethod('handle'))->toBeTrue();
    });

    it('handle method accepts request and closure', function () {
        $reflection = new ReflectionMethod(StructuredLoggingMiddleware::class, 'handle');
        $params = $reflection->getParameters();

        expect($params)->toHaveCount(2);
        expect($params[0]->getName())->toBe('request');
        expect($params[1]->getName())->toBe('next');
    });

    it('returns Symfony Response type', function () {
        $reflection = new ReflectionMethod(StructuredLoggingMiddleware::class, 'handle');
        $returnType = $reflection->getReturnType();

        expect($returnType)->not->toBeNull();
        expect($returnType->getName())->toBe('Symfony\Component\HttpFoundation\Response');
    });

    it('is registered as structured.logging middleware alias', function () {
        // Verify the middleware alias is registered in bootstrap/app.php
        $content = file_get_contents(dirname(__DIR__, 3) . '/bootstrap/app.php');
        expect($content)->toContain("'structured.logging'");
        expect($content)->toContain('StructuredLoggingMiddleware');
    });
});
