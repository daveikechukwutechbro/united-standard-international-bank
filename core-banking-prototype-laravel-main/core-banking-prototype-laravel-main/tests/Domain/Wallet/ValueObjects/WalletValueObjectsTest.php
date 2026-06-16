<?php

declare(strict_types=1);

namespace Tests\Domain\Wallet\ValueObjects;

use App\Domain\Wallet\ValueObjects\TransactionResult;
use App\Domain\Wallet\ValueObjects\WalletAddress;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Wallet domain value objects.
 */
class WalletValueObjectsTest extends TestCase
{
    // WalletAddress Tests

    public function test_wallet_address_stores_properties(): void
    {
        $address = new WalletAddress(
            address: '0x742d35Cc6634C0532925a3b844Bc9e7595f',
            blockchain: 'ethereum',
            label: 'Main Wallet'
        );

        $this->assertEquals('0x742d35Cc6634C0532925a3b844Bc9e7595f', $address->address);
        $this->assertEquals('ethereum', $address->blockchain);
        $this->assertEquals('Main Wallet', $address->label);
    }

    public function test_wallet_address_label_is_optional(): void
    {
        $address = new WalletAddress(
            address: 'bc1qar0srrr7xfkvy5l643lydnw9re59gtzzwf5mdq',
            blockchain: 'bitcoin'
        );

        $this->assertNull($address->label);
    }

    public function test_wallet_address_to_array(): void
    {
        $address = new WalletAddress(
            address: '0x742d35Cc6634C0532925a3b844Bc9e7595f',
            blockchain: 'ethereum',
            label: 'Savings'
        );

        $array = $address->toArray();

        $this->assertEquals([
            'address'    => '0x742d35Cc6634C0532925a3b844Bc9e7595f',
            'blockchain' => 'ethereum',
            'label'      => 'Savings',
        ], $array);
    }

    public function test_wallet_address_to_array_includes_null_label(): void
    {
        $address = new WalletAddress(
            address: '0x742d35Cc6634C0532925a3b844Bc9e7595f',
            blockchain: 'ethereum'
        );

        $array = $address->toArray();

        $this->assertArrayHasKey('label', $array);
        $this->assertNull($array['label']);
    }

    // TransactionResult Tests

    public function test_transaction_result_stores_properties(): void
    {
        $result = new TransactionResult(
            hash: '0xabc123def456',
            status: 'success',
            blockNumber: 12345678,
            gasUsed: '21000',
            effectiveGasPrice: '50000000000'
        );

        $this->assertEquals('0xabc123def456', $result->hash);
        $this->assertEquals('success', $result->status);
        $this->assertEquals(12345678, $result->blockNumber);
        $this->assertEquals('21000', $result->gasUsed);
        $this->assertEquals('50000000000', $result->effectiveGasPrice);
    }

    public function test_transaction_result_is_success_for_success_status(): void
    {
        $result = new TransactionResult(
            hash: '0xabc123',
            status: 'success'
        );

        $this->assertTrue($result->isSuccess());
        $this->assertFalse($result->isPending());
        $this->assertFalse($result->isFailed());
    }

    public function test_transaction_result_is_success_for_hex_status(): void
    {
        $result = new TransactionResult(
            hash: '0xabc123',
            status: '0x1'
        );

        $this->assertTrue($result->isSuccess());
    }

    public function test_transaction_result_is_pending(): void
    {
        $result = new TransactionResult(
            hash: '0xabc123',
            status: 'pending'
        );

        $this->assertTrue($result->isPending());
        $this->assertFalse($result->isSuccess());
        $this->assertFalse($result->isFailed());
    }

    public function test_transaction_result_is_failed_for_failed_status(): void
    {
        $result = new TransactionResult(
            hash: '0xabc123',
            status: 'failed'
        );

        $this->assertTrue($result->isFailed());
        $this->assertFalse($result->isSuccess());
        $this->assertFalse($result->isPending());
    }

    public function test_transaction_result_is_failed_for_hex_status(): void
    {
        $result = new TransactionResult(
            hash: '0xabc123',
            status: '0x0'
        );

        $this->assertTrue($result->isFailed());
    }

    public function test_transaction_result_to_array_filters_null_values(): void
    {
        $result = new TransactionResult(
            hash: '0xabc123',
            status: 'success'
        );

        $array = $result->toArray();

        $this->assertArrayHasKey('hash', $array);
        $this->assertArrayHasKey('status', $array);
        $this->assertArrayNotHasKey('block_number', $array);
        $this->assertArrayNotHasKey('gas_used', $array);
    }

    public function test_transaction_result_to_array_includes_all_set_values(): void
    {
        $result = new TransactionResult(
            hash: '0xabc123',
            status: 'success',
            blockNumber: 12345678,
            gasUsed: '21000',
            effectiveGasPrice: '50000000000',
            logs: ['log1', 'log2'],
            metadata: ['key' => 'value']
        );

        $array = $result->toArray();

        $this->assertEquals('0xabc123', $array['hash']);
        $this->assertEquals('success', $array['status']);
        $this->assertEquals(12345678, $array['block_number']);
        $this->assertEquals('21000', $array['gas_used']);
        $this->assertEquals('50000000000', $array['effective_gas_price']);
        $this->assertEquals(['log1', 'log2'], $array['logs']);
        $this->assertEquals(['key' => 'value'], $array['metadata']);
    }

    public function test_transaction_result_includes_empty_metadata_in_array(): void
    {
        $result = new TransactionResult(
            hash: '0xabc123',
            status: 'success',
            metadata: []
        );

        $array = $result->toArray();

        // Empty array is not filtered out (only null values are)
        $this->assertArrayHasKey('metadata', $array);
        $this->assertEquals([], $array['metadata']);
    }

    public function test_transaction_result_preserves_non_empty_metadata(): void
    {
        $result = new TransactionResult(
            hash: '0xabc123',
            status: 'success',
            metadata: ['txType' => 'transfer']
        );

        $this->assertEquals(['txType' => 'transfer'], $result->metadata);
    }
}
