<?php

declare(strict_types=1);

namespace App\Domain\Shared\EventSourcing;

/**
 * Routes events to domain-specific tables by namespace.
 *
 * Extracts the domain name from event class namespaces (App\Domain\{Domain}\Events\...)
 * and maps them to dedicated event storage tables. Falls back to `stored_events`
 * for unmapped domains.
 */
class EventRouter implements EventRouterInterface
{
    /** @var array<string, string> */
    private array $domainTableMap;

    private string $defaultTable;

    /**
     * @param array<string, string> $domainTableMap Domain => table name mapping
     * @param string $defaultTable Fallback table for unmapped domains
     */
    public function __construct(
        array $domainTableMap = [],
        string $defaultTable = 'stored_events',
    ) {
        $this->domainTableMap = $domainTableMap ?: $this->getDefaultDomainTableMap();
        $this->defaultTable = $defaultTable;
    }

    public function resolveTableForEvent(string $eventClass): string
    {
        $domain = $this->extractDomain($eventClass);

        return $this->resolveTableForDomain($domain);
    }

    public function resolveTableForDomain(string $domain): string
    {
        return $this->domainTableMap[$domain] ?? $this->defaultTable;
    }

    public function extractDomain(string $eventClass): string
    {
        // Match App\Domain\{Domain}\... pattern
        if (preg_match('/^App\\\\Domain\\\\([^\\\\]+)\\\\/', $eventClass, $matches)) {
            return $matches[1];
        }

        return 'Unknown';
    }

    public function getDomainTableMap(): array
    {
        return $this->domainTableMap;
    }

    public function getDefaultTable(): string
    {
        return $this->defaultTable;
    }

    /**
     * Default domain-to-table mapping.
     *
     * Each domain gets a dedicated `{domain}_events` table.
     * This activates the domain-specific event tables that exist
     * but were previously unused (all events went to `stored_events`).
     *
     * @return array<string, string>
     */
    private function getDefaultDomainTableMap(): array
    {
        return [
            'Account'              => 'account_events',
            'AgentProtocol'        => 'agent_protocol_events',
            'AI'                   => 'ai_events',
            'Asset'                => 'asset_events',
            'Batch'                => 'batch_events',
            'Cgo'                  => 'cgo_events',
            'Compliance'           => 'compliance_events',
            'Exchange'             => 'exchange_events',
            'Lending'              => 'lending_events',
            'Mobile'               => 'mobile_events',
            'Monitoring'           => 'monitoring_events',
            'Payment'              => 'payment_events',
            'Performance'          => 'performance_events',
            'Product'              => 'product_events',
            'Stablecoin'           => 'stablecoin_events',
            'Treasury'             => 'treasury_events',
            'User'                 => 'user_events',
            'Wallet'               => 'wallet_events',
            'CrossChain'           => 'cross_chain_events',
            'DeFi'                 => 'defi_events',
            'Privacy'              => 'privacy_events',
            'Banking'              => 'banking_events',
            'Basket'               => 'basket_events',
            'CardIssuance'         => 'card_issuance_events',
            'Commerce'             => 'commerce_events',
            'Custodian'            => 'custodian_events',
            'FinancialInstitution' => 'financial_institution_events',
            'Fraud'                => 'fraud_events',
            'KeyManagement'        => 'key_management_events',
            'MobilePayment'        => 'mobile_payment_events',
            'Regulatory'           => 'regulatory_events',
            'Relayer'              => 'relayer_events',
            'TrustCert'            => 'trust_cert_events',
        ];
    }
}
