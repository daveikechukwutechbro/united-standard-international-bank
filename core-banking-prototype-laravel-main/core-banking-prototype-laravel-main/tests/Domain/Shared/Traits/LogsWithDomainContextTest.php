<?php

declare(strict_types=1);

use App\Domain\Shared\Traits\LogsWithDomainContext;
use Illuminate\Support\Facades\Log;

// Create a test class that uses the trait, simulating a domain service
$testClass = new class () {
    use LogsWithDomainContext;

    public function getTestDomain(): string
    {
        return $this->getDomainName();
    }

    public function testLogDebug(string $message, array $context = []): void
    {
        $this->logDebug($message, $context);
    }

    public function testLogInfo(string $message, array $context = []): void
    {
        $this->logInfo($message, $context);
    }

    public function testLogWarning(string $message, array $context = []): void
    {
        $this->logWarning($message, $context);
    }

    public function testLogError(string $message, array $context = []): void
    {
        $this->logError($message, $context);
    }
};

describe('LogsWithDomainContext', function () use ($testClass) {
    it('extracts domain name as Unknown for non-domain classes', function () use ($testClass) {
        // Anonymous class won't match the App\Domain\X pattern
        expect($testClass->getTestDomain())->toBe('Unknown');
    });

    it('logs debug with domain context', function () use ($testClass) {
        Log::spy();

        $testClass->testLogDebug('Test debug message', ['key' => 'value']);

        Log::shouldHaveReceived('log')
            ->withArgs(function ($level, $message, $context) {
                return $level === 'debug'
                    && $message === 'Test debug message'
                    && isset($context['domain'])
                    && isset($context['service'])
                    && $context['key'] === 'value';
            })
            ->once();
    });

    it('logs info with domain context', function () use ($testClass) {
        Log::spy();

        $testClass->testLogInfo('Test info message');

        Log::shouldHaveReceived('log')
            ->withArgs(function ($level, $message, $context) {
                return $level === 'info'
                    && $message === 'Test info message'
                    && isset($context['domain'])
                    && isset($context['service']);
            })
            ->once();
    });

    it('logs warning with domain context', function () use ($testClass) {
        Log::spy();

        $testClass->testLogWarning('Test warning');

        Log::shouldHaveReceived('log')
            ->withArgs(fn ($level) => $level === 'warning')
            ->once();
    });

    it('logs error with domain context', function () use ($testClass) {
        Log::spy();

        $testClass->testLogError('Test error');

        Log::shouldHaveReceived('log')
            ->withArgs(fn ($level) => $level === 'error')
            ->once();
    });

    it('includes service class name in context', function () use ($testClass) {
        Log::spy();

        $testClass->testLogInfo('Service check');

        Log::shouldHaveReceived('log')
            ->withArgs(function ($level, $message, $context) {
                return isset($context['service']) && is_string($context['service']);
            })
            ->once();
    });
});
