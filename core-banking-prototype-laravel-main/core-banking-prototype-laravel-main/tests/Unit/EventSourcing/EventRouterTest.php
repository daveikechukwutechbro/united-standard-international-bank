<?php

declare(strict_types=1);

use App\Domain\Shared\EventSourcing\EventRouter;

describe('EventRouter', function () {
    it('resolves table for known domain event class', function () {
        $router = new EventRouter();

        $table = $router->resolveTableForEvent('App\\Domain\\Account\\Events\\MoneyAdded');

        expect($table)->toBe('account_events');
    });

    it('resolves table for Exchange domain', function () {
        $router = new EventRouter();

        $table = $router->resolveTableForEvent('App\\Domain\\Exchange\\Events\\OrderPlaced');

        expect($table)->toBe('exchange_events');
    });

    it('falls back to stored_events for unknown domain', function () {
        $router = new EventRouter();

        $table = $router->resolveTableForEvent('App\\Domain\\UnknownDomain\\Events\\SomeEvent');

        expect($table)->toBe('stored_events');
    });

    it('falls back to stored_events for non-domain event class', function () {
        $router = new EventRouter();

        $table = $router->resolveTableForEvent('Spatie\\EventSourcing\\Events\\SomeEvent');

        expect($table)->toBe('stored_events');
    });

    it('extracts domain from event class namespace', function () {
        $router = new EventRouter();

        expect($router->extractDomain('App\\Domain\\Account\\Events\\MoneyAdded'))->toBe('Account');
        expect($router->extractDomain('App\\Domain\\Compliance\\Events\\AlertCreated'))->toBe('Compliance');
        expect($router->extractDomain('App\\Domain\\Treasury\\Events\\Portfolio\\PortfolioCreated'))->toBe('Treasury');
        expect($router->extractDomain('Spatie\\EventSourcing\\Events\\SomeEvent'))->toBe('Unknown');
    });

    it('resolves table for domain name directly', function () {
        $router = new EventRouter();

        expect($router->resolveTableForDomain('Account'))->toBe('account_events');
        expect($router->resolveTableForDomain('Wallet'))->toBe('wallet_events');
        expect($router->resolveTableForDomain('Compliance'))->toBe('compliance_events');
        expect($router->resolveTableForDomain('UnknownDomain'))->toBe('stored_events');
    });

    it('returns complete domain table map', function () {
        $router = new EventRouter();

        $map = $router->getDomainTableMap();

        expect($map)->toBeArray();
        expect($map)->toHaveKey('Account');
        expect($map)->toHaveKey('Exchange');
        expect($map)->toHaveKey('Wallet');
        expect($map)->toHaveKey('Treasury');
        expect($map['Account'])->toBe('account_events');
    });

    it('returns default table name', function () {
        $router = new EventRouter();

        expect($router->getDefaultTable())->toBe('stored_events');
    });

    it('accepts custom domain table map', function () {
        $router = new EventRouter(
            domainTableMap: ['CustomDomain' => 'custom_events'],
            defaultTable: 'fallback_events',
        );

        expect($router->resolveTableForDomain('CustomDomain'))->toBe('custom_events');
        expect($router->resolveTableForDomain('Other'))->toBe('fallback_events');
        expect($router->getDefaultTable())->toBe('fallback_events');
    });

    it('maps all core domains', function () {
        $router = new EventRouter();
        $map = $router->getDomainTableMap();

        $expectedDomains = [
            'Account', 'AgentProtocol', 'AI', 'Asset', 'Batch', 'Cgo',
            'Compliance', 'Exchange', 'Lending', 'Mobile', 'Monitoring',
            'Payment', 'Performance', 'Product', 'Stablecoin', 'Treasury',
            'User', 'Wallet', 'CrossChain', 'DeFi', 'Privacy',
            'Banking', 'Basket', 'CardIssuance', 'Commerce', 'Custodian',
            'FinancialInstitution', 'Fraud', 'KeyManagement', 'MobilePayment',
            'Regulatory', 'Relayer', 'TrustCert',
        ];

        expect($map)->toHaveCount(33);

        foreach ($expectedDomains as $domain) {
            expect($map)->toHaveKey($domain);
        }
    });
});
