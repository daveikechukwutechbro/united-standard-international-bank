<?php

declare(strict_types=1);

namespace App\Domain\ISO20022\Services;

use App\Domain\ISO20022\ValueObjects\Camt053;
use App\Domain\ISO20022\ValueObjects\Camt054;
use App\Domain\ISO20022\ValueObjects\Pacs002;
use App\Domain\ISO20022\ValueObjects\Pacs003;
use App\Domain\ISO20022\ValueObjects\Pacs004;
use App\Domain\ISO20022\ValueObjects\Pacs008;
use App\Domain\ISO20022\ValueObjects\Pain001;
use App\Domain\ISO20022\ValueObjects\Pain008;

final class MessageRegistry
{
    /** @var array<string, class-string> */
    private const MESSAGE_MAP = [
        'pain.001' => Pain001::class,
        'pain.008' => Pain008::class,
        'pacs.008' => Pacs008::class,
        'pacs.002' => Pacs002::class,
        'pacs.003' => Pacs003::class,
        'pacs.004' => Pacs004::class,
        'camt.053' => Camt053::class,
        'camt.054' => Camt054::class,
    ];

    /** @var array<string, string> */
    private const NAMESPACE_MAP = [
        'pain.001' => 'urn:iso:std:iso:20022:tech:xsd:pain.001.001.09',
        'pain.008' => 'urn:iso:std:iso:20022:tech:xsd:pain.008.001.08',
        'pacs.008' => 'urn:iso:std:iso:20022:tech:xsd:pacs.008.001.08',
        'pacs.002' => 'urn:iso:std:iso:20022:tech:xsd:pacs.002.001.10',
        'pacs.003' => 'urn:iso:std:iso:20022:tech:xsd:pacs.003.001.08',
        'pacs.004' => 'urn:iso:std:iso:20022:tech:xsd:pacs.004.001.09',
        'camt.053' => 'urn:iso:std:iso:20022:tech:xsd:camt.053.001.08',
        'camt.054' => 'urn:iso:std:iso:20022:tech:xsd:camt.054.001.08',
    ];

    /** @return class-string|null */
    public function getDtoClass(string $messageType): ?string
    {
        return self::MESSAGE_MAP[$messageType] ?? null;
    }

    public function getNamespace(string $messageType): ?string
    {
        return self::NAMESPACE_MAP[$messageType] ?? null;
    }

    public function isSupported(string $messageType): bool
    {
        return isset(self::MESSAGE_MAP[$messageType]);
    }

    /** @return array<string> */
    public function supportedTypes(): array
    {
        return array_keys(self::MESSAGE_MAP);
    }

    public function detectMessageType(string $xml): ?string
    {
        foreach (self::NAMESPACE_MAP as $type => $namespace) {
            if (str_contains($xml, $namespace)) {
                return $type;
            }
        }

        return null;
    }
}
