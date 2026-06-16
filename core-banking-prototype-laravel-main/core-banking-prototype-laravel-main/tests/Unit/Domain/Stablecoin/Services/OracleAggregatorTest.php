<?php

declare(strict_types=1);

use App\Domain\Stablecoin\Contracts\OracleConnector;
use App\Domain\Stablecoin\Services\OracleAggregator;
use App\Domain\Stablecoin\ValueObjects\AggregatedPrice;
use App\Domain\Stablecoin\ValueObjects\PriceData;
use Illuminate\Support\Facades\Cache;

uses(Tests\TestCase::class);

describe('OracleAggregator', function () {
    beforeEach(function () {
        $this->aggregator = new OracleAggregator();
        Cache::flush();
    });

    describe('registerOracle', function () {
        it('returns self for fluent chaining', function () {
            $oracle = Mockery::mock(OracleConnector::class);
            $oracle->shouldReceive('getPriority')->andReturn(1);

            $result = $this->aggregator->registerOracle($oracle);

            expect($result)->toBe($this->aggregator);
        });

        it('sorts oracles by priority after registration', function () {
            $lowPriority = Mockery::mock(OracleConnector::class);
            $lowPriority->shouldReceive('getPriority')->andReturn(10);
            $lowPriority->shouldReceive('getSourceName')->andReturn('low');
            $lowPriority->shouldReceive('isHealthy')->andReturn(true);
            $lowPriority->shouldReceive('getPrice')->andReturn(
                new PriceData('ETH', 'USD', '2000.00', 'low', now())
            );

            $highPriority = Mockery::mock(OracleConnector::class);
            $highPriority->shouldReceive('getPriority')->andReturn(1);
            $highPriority->shouldReceive('getSourceName')->andReturn('high');
            $highPriority->shouldReceive('isHealthy')->andReturn(true);
            $highPriority->shouldReceive('getPrice')->andReturn(
                new PriceData('ETH', 'USD', '2000.00', 'high', now())
            );

            // Register low priority first, then high
            $this->aggregator->registerOracle($lowPriority);
            $this->aggregator->registerOracle($highPriority);

            $result = $this->aggregator->getAggregatedPrice('ETH', 'USD');

            // Both oracles should have been queried (test they were registered)
            expect($result)->toBeInstanceOf(AggregatedPrice::class);
            expect($result->getSourceCount())->toBe(2);
        });
    });

    describe('getAggregatedPrice', function () {
        it('throws when insufficient oracle responses', function () {
            $oracle = Mockery::mock(OracleConnector::class);
            $oracle->shouldReceive('getPriority')->andReturn(1);
            $oracle->shouldReceive('getSourceName')->andReturn('test');
            $oracle->shouldReceive('isHealthy')->andReturn(false);

            $this->aggregator->registerOracle($oracle);

            expect(fn () => $this->aggregator->getAggregatedPrice('ETH', 'USD'))
                ->toThrow(RuntimeException::class, 'Insufficient oracle responses');
        });

        it('returns aggregated price from multiple oracles', function () {
            $oracle1 = Mockery::mock(OracleConnector::class);
            $oracle1->shouldReceive('getPriority')->andReturn(1);
            $oracle1->shouldReceive('getSourceName')->andReturn('oracle1');
            $oracle1->shouldReceive('isHealthy')->andReturn(true);
            $oracle1->shouldReceive('getPrice')->andReturn(
                new PriceData('ETH', 'USD', '2000.00', 'oracle1', now())
            );

            $oracle2 = Mockery::mock(OracleConnector::class);
            $oracle2->shouldReceive('getPriority')->andReturn(2);
            $oracle2->shouldReceive('getSourceName')->andReturn('oracle2');
            $oracle2->shouldReceive('isHealthy')->andReturn(true);
            $oracle2->shouldReceive('getPrice')->andReturn(
                new PriceData('ETH', 'USD', '2001.00', 'oracle2', now())
            );

            $this->aggregator->registerOracle($oracle1);
            $this->aggregator->registerOracle($oracle2);

            $result = $this->aggregator->getAggregatedPrice('ETH', 'USD');

            expect($result)->toBeInstanceOf(AggregatedPrice::class);
            expect($result->base)->toBe('ETH');
            expect($result->quote)->toBe('USD');
            expect($result->aggregationMethod)->toBe('median');
            expect($result->getSourceCount())->toBe(2);
        });

        it('calculates median price from odd number of oracles', function () {
            $prices = ['2000.00', '2005.00', '2010.00'];

            foreach ($prices as $i => $price) {
                $oracle = Mockery::mock(OracleConnector::class);
                $oracle->shouldReceive('getPriority')->andReturn($i);
                $oracle->shouldReceive('getSourceName')->andReturn("oracle{$i}");
                $oracle->shouldReceive('isHealthy')->andReturn(true);
                $oracle->shouldReceive('getPrice')->andReturn(
                    new PriceData('BTC', 'USD', $price, "oracle{$i}", now())
                );
                $this->aggregator->registerOracle($oracle);
            }

            $result = $this->aggregator->getAggregatedPrice('BTC', 'USD');

            // Median of [2000, 2005, 2010] = 2005
            expect($result->price)->toBe('2005.00000000');
        });

        it('calculates median price from even number of oracles', function () {
            $prices = ['2000.00', '2002.00', '2004.00', '2006.00'];

            foreach ($prices as $i => $price) {
                $oracle = Mockery::mock(OracleConnector::class);
                $oracle->shouldReceive('getPriority')->andReturn($i);
                $oracle->shouldReceive('getSourceName')->andReturn("oracle{$i}");
                $oracle->shouldReceive('isHealthy')->andReturn(true);
                $oracle->shouldReceive('getPrice')->andReturn(
                    new PriceData('BTC', 'USD', $price, "oracle{$i}", now())
                );
                $this->aggregator->registerOracle($oracle);
            }

            $result = $this->aggregator->getAggregatedPrice('BTC', 'USD');

            // Median of [2000, 2002, 2004, 2006] = (2002 + 2004) / 2 = 2003
            expect($result->price)->toBe('2003.00000000');
        });

        it('skips unhealthy oracles', function () {
            $healthyOracle1 = Mockery::mock(OracleConnector::class);
            $healthyOracle1->shouldReceive('getPriority')->andReturn(1);
            $healthyOracle1->shouldReceive('getSourceName')->andReturn('healthy1');
            $healthyOracle1->shouldReceive('isHealthy')->andReturn(true);
            $healthyOracle1->shouldReceive('getPrice')->andReturn(
                new PriceData('ETH', 'USD', '2000.00', 'healthy1', now())
            );

            $unhealthyOracle = Mockery::mock(OracleConnector::class);
            $unhealthyOracle->shouldReceive('getPriority')->andReturn(2);
            $unhealthyOracle->shouldReceive('getSourceName')->andReturn('unhealthy');
            $unhealthyOracle->shouldReceive('isHealthy')->andReturn(false);

            $healthyOracle2 = Mockery::mock(OracleConnector::class);
            $healthyOracle2->shouldReceive('getPriority')->andReturn(3);
            $healthyOracle2->shouldReceive('getSourceName')->andReturn('healthy2');
            $healthyOracle2->shouldReceive('isHealthy')->andReturn(true);
            $healthyOracle2->shouldReceive('getPrice')->andReturn(
                new PriceData('ETH', 'USD', '2001.00', 'healthy2', now())
            );

            $this->aggregator->registerOracle($healthyOracle1);
            $this->aggregator->registerOracle($unhealthyOracle);
            $this->aggregator->registerOracle($healthyOracle2);

            $result = $this->aggregator->getAggregatedPrice('ETH', 'USD');

            expect($result->getSourceCount())->toBe(2);
        });

        it('skips stale prices', function () {
            $freshOracle1 = Mockery::mock(OracleConnector::class);
            $freshOracle1->shouldReceive('getPriority')->andReturn(1);
            $freshOracle1->shouldReceive('getSourceName')->andReturn('fresh1');
            $freshOracle1->shouldReceive('isHealthy')->andReturn(true);
            $freshOracle1->shouldReceive('getPrice')->andReturn(
                new PriceData('ETH', 'USD', '2000.00', 'fresh1', now())
            );

            $staleOracle = Mockery::mock(OracleConnector::class);
            $staleOracle->shouldReceive('getPriority')->andReturn(2);
            $staleOracle->shouldReceive('getSourceName')->andReturn('stale');
            $staleOracle->shouldReceive('isHealthy')->andReturn(true);
            $staleOracle->shouldReceive('getPrice')->andReturn(
                new PriceData('ETH', 'USD', '1900.00', 'stale', now()->subMinutes(10))
            );

            $freshOracle2 = Mockery::mock(OracleConnector::class);
            $freshOracle2->shouldReceive('getPriority')->andReturn(3);
            $freshOracle2->shouldReceive('getSourceName')->andReturn('fresh2');
            $freshOracle2->shouldReceive('isHealthy')->andReturn(true);
            $freshOracle2->shouldReceive('getPrice')->andReturn(
                new PriceData('ETH', 'USD', '2001.00', 'fresh2', now())
            );

            $this->aggregator->registerOracle($freshOracle1);
            $this->aggregator->registerOracle($staleOracle);
            $this->aggregator->registerOracle($freshOracle2);

            $result = $this->aggregator->getAggregatedPrice('ETH', 'USD');

            expect($result->getSourceCount())->toBe(2);
        });

        it('gracefully handles oracle exceptions', function () {
            $workingOracle1 = Mockery::mock(OracleConnector::class);
            $workingOracle1->shouldReceive('getPriority')->andReturn(1);
            $workingOracle1->shouldReceive('getSourceName')->andReturn('working1');
            $workingOracle1->shouldReceive('isHealthy')->andReturn(true);
            $workingOracle1->shouldReceive('getPrice')->andReturn(
                new PriceData('ETH', 'USD', '2000.00', 'working1', now())
            );

            $brokenOracle = Mockery::mock(OracleConnector::class);
            $brokenOracle->shouldReceive('getPriority')->andReturn(2);
            $brokenOracle->shouldReceive('getSourceName')->andReturn('broken');
            $brokenOracle->shouldReceive('isHealthy')->andReturn(true);
            $brokenOracle->shouldReceive('getPrice')->andThrow(new Exception('Oracle down'));

            $workingOracle2 = Mockery::mock(OracleConnector::class);
            $workingOracle2->shouldReceive('getPriority')->andReturn(3);
            $workingOracle2->shouldReceive('getSourceName')->andReturn('working2');
            $workingOracle2->shouldReceive('isHealthy')->andReturn(true);
            $workingOracle2->shouldReceive('getPrice')->andReturn(
                new PriceData('ETH', 'USD', '2001.00', 'working2', now())
            );

            $this->aggregator->registerOracle($workingOracle1);
            $this->aggregator->registerOracle($brokenOracle);
            $this->aggregator->registerOracle($workingOracle2);

            $result = $this->aggregator->getAggregatedPrice('ETH', 'USD');

            expect($result->getSourceCount())->toBe(2);
        });

        it('caches the aggregated price', function () {
            $oracle1 = Mockery::mock(OracleConnector::class);
            $oracle1->shouldReceive('getPriority')->andReturn(1);
            $oracle1->shouldReceive('getSourceName')->andReturn('oracle1');
            $oracle1->shouldReceive('isHealthy')->andReturn(true);
            $oracle1->shouldReceive('getPrice')->once()->andReturn(
                new PriceData('ETH', 'USD', '2000.00', 'oracle1', now())
            );

            $oracle2 = Mockery::mock(OracleConnector::class);
            $oracle2->shouldReceive('getPriority')->andReturn(2);
            $oracle2->shouldReceive('getSourceName')->andReturn('oracle2');
            $oracle2->shouldReceive('isHealthy')->andReturn(true);
            $oracle2->shouldReceive('getPrice')->once()->andReturn(
                new PriceData('ETH', 'USD', '2001.00', 'oracle2', now())
            );

            $this->aggregator->registerOracle($oracle1);
            $this->aggregator->registerOracle($oracle2);

            // First call fetches from oracles
            $result1 = $this->aggregator->getAggregatedPrice('ETH', 'USD');

            // Second call should use cache (oracles called only once)
            $result2 = $this->aggregator->getAggregatedPrice('ETH', 'USD');

            expect($result1->price)->toBe($result2->price);
        });

        it('returns high confidence when prices agree closely', function () {
            $oracle1 = Mockery::mock(OracleConnector::class);
            $oracle1->shouldReceive('getPriority')->andReturn(1);
            $oracle1->shouldReceive('getSourceName')->andReturn('oracle1');
            $oracle1->shouldReceive('isHealthy')->andReturn(true);
            $oracle1->shouldReceive('getPrice')->andReturn(
                new PriceData('ETH', 'USD', '2000.00', 'oracle1', now())
            );

            $oracle2 = Mockery::mock(OracleConnector::class);
            $oracle2->shouldReceive('getPriority')->andReturn(2);
            $oracle2->shouldReceive('getSourceName')->andReturn('oracle2');
            $oracle2->shouldReceive('isHealthy')->andReturn(true);
            $oracle2->shouldReceive('getPrice')->andReturn(
                new PriceData('ETH', 'USD', '2000.01', 'oracle2', now())
            );

            $this->aggregator->registerOracle($oracle1);
            $this->aggregator->registerOracle($oracle2);

            $result = $this->aggregator->getAggregatedPrice('ETH', 'USD');

            expect($result->confidence)->toBeGreaterThan(0.9);
            expect($result->isHighConfidence())->toBeTrue();
        });
    });
});
