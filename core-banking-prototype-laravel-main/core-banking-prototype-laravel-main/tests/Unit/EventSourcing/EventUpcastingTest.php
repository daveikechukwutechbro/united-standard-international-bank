<?php

declare(strict_types=1);

use App\Domain\Shared\EventSourcing\AbstractEventUpcaster;
use App\Domain\Shared\EventSourcing\EventUpcastingService;
use App\Domain\Shared\EventSourcing\EventVersionRegistry;

function createMoneyAddedV1ToV2Upcaster(): AbstractEventUpcaster
{
    return new class () extends AbstractEventUpcaster {
        public function __construct()
        {
            parent::__construct('money_added', 1, 2);
        }

        public function upcast(array $payload): array
        {
            $payload['currency'] = $payload['currency'] ?? 'USD';

            return $payload;
        }
    };
}

function createMoneyAddedV2ToV3Upcaster(): AbstractEventUpcaster
{
    return new class () extends AbstractEventUpcaster {
        public function __construct()
        {
            parent::__construct('money_added', 2, 3);
        }

        public function upcast(array $payload): array
        {
            if (isset($payload['money'])) {
                $payload['amount'] = $payload['money'];
                unset($payload['money']);
            }
            $payload['source'] = $payload['source'] ?? 'internal';

            return $payload;
        }
    };
}

describe('EventVersionRegistry', function () {
    it('registers upcasters and tracks versions', function () {
        $registry = new EventVersionRegistry();
        $registry->register(createMoneyAddedV1ToV2Upcaster());

        expect($registry->hasUpcasters('money_added'))->toBeTrue();
        expect($registry->getCurrentVersion('money_added'))->toBe(2);
        expect($registry->hasUpcasters('unknown_event'))->toBeFalse();
    });

    it('tracks highest version across multiple upcasters', function () {
        $registry = new EventVersionRegistry();
        $registry->register(createMoneyAddedV1ToV2Upcaster());
        $registry->register(createMoneyAddedV2ToV3Upcaster());

        expect($registry->getCurrentVersion('money_added'))->toBe(3);
    });

    it('returns ordered upcasters', function () {
        $registry = new EventVersionRegistry();
        // Register in reverse order
        $registry->register(createMoneyAddedV2ToV3Upcaster());
        $registry->register(createMoneyAddedV1ToV2Upcaster());

        $upcasters = $registry->getUpcasters('money_added');

        expect($upcasters)->toHaveCount(2);
        expect($upcasters[0]->fromVersion())->toBe(1);
        expect($upcasters[1]->fromVersion())->toBe(2);
    });

    it('builds upcast chain from specific version', function () {
        $registry = new EventVersionRegistry();
        $registry->register(createMoneyAddedV1ToV2Upcaster());
        $registry->register(createMoneyAddedV2ToV3Upcaster());

        // From v1 → should chain v1→v2, v2→v3
        $chain = $registry->getUpcastChain('money_added', 1);
        expect($chain)->toHaveCount(2);

        // From v2 → should only chain v2→v3
        $chain = $registry->getUpcastChain('money_added', 2);
        expect($chain)->toHaveCount(1);
        expect($chain[0]->fromVersion())->toBe(2);

        // From v3 → should be empty (already latest)
        $chain = $registry->getUpcastChain('money_added', 3);
        expect($chain)->toHaveCount(0);
    });

    it('detects chain gaps in upcaster versions', function () {
        $registry = new EventVersionRegistry();

        // Register v1→v2 upcaster
        $registry->register(createMoneyAddedV1ToV2Upcaster());

        // Create a v3→v4 upcaster (skipping v2→v3)
        $v3ToV4 = new class () extends AbstractEventUpcaster {
            public function __construct()
            {
                parent::__construct('gap_event', 3, 4);
            }

            public function upcast(array $payload): array
            {
                return $payload;
            }
        };

        $v1ToV2 = new class () extends AbstractEventUpcaster {
            public function __construct()
            {
                parent::__construct('gap_event', 1, 2);
            }

            public function upcast(array $payload): array
            {
                return $payload;
            }
        };

        $registry->register($v1ToV2);
        $registry->register($v3ToV4);

        $gaps = $registry->validateChain('gap_event');

        expect($gaps)->not->toBeEmpty();
        expect($gaps[0])->toContain('v2');
        expect($gaps[0])->toContain('v3');
    });

    it('reports no gaps for complete chain', function () {
        $registry = new EventVersionRegistry();
        $registry->register(createMoneyAddedV1ToV2Upcaster());
        $registry->register(createMoneyAddedV2ToV3Upcaster());

        $gaps = $registry->validateChain('money_added');

        expect($gaps)->toBeEmpty();
    });

    it('lists all versions', function () {
        $registry = new EventVersionRegistry();
        $registry->register(createMoneyAddedV1ToV2Upcaster());
        $registry->register(createMoneyAddedV2ToV3Upcaster());

        $versions = $registry->getAllVersions();

        expect($versions)->toHaveKey('money_added');
        expect($versions['money_added']['current_version'])->toBe(3);
        expect($versions['money_added']['upcaster_count'])->toBe(2);
    });
});

describe('EventUpcastingService', function () {
    it('upcasts event from v1 to latest', function () {
        $registry = new EventVersionRegistry();
        $registry->register(createMoneyAddedV1ToV2Upcaster());
        $registry->register(createMoneyAddedV2ToV3Upcaster());

        $service = new EventUpcastingService($registry);

        $payload = ['money' => 1000, 'hash' => 'abc123'];
        $result = $service->upcast('money_added', $payload, 1);

        expect($result['upcasted'])->toBeTrue();
        expect($result['version'])->toBe(3);
        expect($result['payload']['amount'])->toBe(1000);
        expect($result['payload']['currency'])->toBe('USD');
        expect($result['payload']['source'])->toBe('internal');
        expect($result['payload'])->not->toHaveKey('money');
    });

    it('upcasts from intermediate version', function () {
        $registry = new EventVersionRegistry();
        $registry->register(createMoneyAddedV1ToV2Upcaster());
        $registry->register(createMoneyAddedV2ToV3Upcaster());

        $service = new EventUpcastingService($registry);

        $payload = ['money' => 500, 'currency' => 'EUR'];
        $result = $service->upcast('money_added', $payload, 2);

        expect($result['upcasted'])->toBeTrue();
        expect($result['version'])->toBe(3);
        expect($result['payload']['amount'])->toBe(500);
        expect($result['payload']['currency'])->toBe('EUR');
    });

    it('returns unchanged payload when no upcasters exist', function () {
        $registry = new EventVersionRegistry();
        $service = new EventUpcastingService($registry);

        $payload = ['money' => 1000];
        $result = $service->upcast('unknown_event', $payload, 1);

        expect($result['upcasted'])->toBeFalse();
        expect($result['version'])->toBe(1);
        expect($result['payload'])->toBe($payload);
    });

    it('returns unchanged payload when already at latest version', function () {
        $registry = new EventVersionRegistry();
        $registry->register(createMoneyAddedV1ToV2Upcaster());

        $service = new EventUpcastingService($registry);

        $payload = ['money' => 1000, 'currency' => 'USD'];
        $result = $service->upcast('money_added', $payload, 2);

        expect($result['upcasted'])->toBeFalse();
        expect($result['version'])->toBe(2);
    });
});

describe('AbstractEventUpcaster', function () {
    it('reports correct event class and versions', function () {
        $upcaster = createMoneyAddedV1ToV2Upcaster();

        expect($upcaster->eventClass())->toBe('money_added');
        expect($upcaster->fromVersion())->toBe(1);
        expect($upcaster->toVersion())->toBe(2);
    });

    it('supports matching event class and version', function () {
        $upcaster = createMoneyAddedV1ToV2Upcaster();

        expect($upcaster->supports('money_added', 1))->toBeTrue();
        expect($upcaster->supports('money_added', 2))->toBeFalse();
        expect($upcaster->supports('other_event', 1))->toBeFalse();
    });
});
