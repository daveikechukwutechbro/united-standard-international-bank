<?php

declare(strict_types=1);

use App\Domain\Custodian\ValueObjects\TransactionReceipt;
use Carbon\Carbon;

it('can create transaction receipt', function () {
    $receipt = new TransactionReceipt(
        id: 'tx-123',
        status: 'completed',
        fromAccount: 'acc-1',
        toAccount: 'acc-2',
        assetCode: 'USD',
        amount: 10000,
        fee: 100,
        reference: 'REF-123',
        createdAt: Carbon::now(),
        completedAt: Carbon::now(),
        metadata: ['test' => true]
    );

    expect($receipt->id)->toBe('tx-123');
    expect($receipt->status)->toBe('completed');
    expect($receipt->fromAccount)->toBe('acc-1');
    expect($receipt->toAccount)->toBe('acc-2');
    expect($receipt->assetCode)->toBe('USD');
    expect($receipt->amount)->toBe(10000);
    expect($receipt->fee)->toBe(100);
    expect($receipt->reference)->toBe('REF-123');
    expect($receipt->metadata)->toBe(['test' => true]);
});

it('can detect completed status', function () {
    $completed = new TransactionReceipt('tx-1', 'completed');
    $success = new TransactionReceipt('tx-2', 'success');
    $settled = new TransactionReceipt('tx-3', 'settled');
    $pending = new TransactionReceipt('tx-4', 'pending');

    expect($completed->isCompleted())->toBeTrue();
    expect($success->isCompleted())->toBeTrue();
    expect($settled->isCompleted())->toBeTrue();
    expect($pending->isCompleted())->toBeFalse();
});

it('can detect pending status', function () {
    $pending = new TransactionReceipt('tx-1', 'pending');
    $processing = new TransactionReceipt('tx-2', 'processing');
    $initiated = new TransactionReceipt('tx-3', 'initiated');
    $completed = new TransactionReceipt('tx-4', 'completed');

    expect($pending->isPending())->toBeTrue();
    expect($processing->isPending())->toBeTrue();
    expect($initiated->isPending())->toBeTrue();
    expect($completed->isPending())->toBeFalse();
});

it('can detect failed status', function () {
    $failed = new TransactionReceipt('tx-1', 'failed');
    $rejected = new TransactionReceipt('tx-2', 'rejected');
    $cancelled = new TransactionReceipt('tx-3', 'cancelled');
    $completed = new TransactionReceipt('tx-4', 'completed');

    expect($failed->isFailed())->toBeTrue();
    expect($rejected->isFailed())->toBeTrue();
    expect($cancelled->isFailed())->toBeTrue();
    expect($completed->isFailed())->toBeFalse();
});

it('can convert to array', function () {
    $createdAt = Carbon::now();
    $completedAt = Carbon::now()->addMinutes(1);

    $receipt = new TransactionReceipt(
        id: 'tx-123',
        status: 'completed',
        fromAccount: 'acc-1',
        toAccount: 'acc-2',
        assetCode: 'USD',
        amount: 10000,
        fee: 100,
        reference: 'REF-123',
        createdAt: $createdAt,
        completedAt: $completedAt,
        metadata: ['test' => true]
    );

    $array = $receipt->toArray();

    expect($array)->toHaveKeys([
        'id', 'status', 'from_account', 'to_account',
        'asset_code', 'amount', 'fee', 'reference',
        'created_at', 'completed_at', 'metadata',
    ]);

    expect($array['id'])->toBe('tx-123');
    expect($array['status'])->toBe('completed');
    expect($array['from_account'])->toBe('acc-1');
    expect($array['to_account'])->toBe('acc-2');
    expect($array['asset_code'])->toBe('USD');
    expect($array['amount'])->toBe(10000);
    expect($array['fee'])->toBe(100);
    expect($array['reference'])->toBe('REF-123');
    expect($array['created_at'])->toBe($createdAt->toISOString());
    expect($array['completed_at'])->toBe($completedAt->toISOString());
    expect($array['metadata'])->toBe(['test' => true]);
});

it('handles null values in toArray', function () {
    $receipt = new TransactionReceipt(
        id: 'tx-123',
        status: 'pending'
    );

    $array = $receipt->toArray();

    expect($array['id'])->toBe('tx-123');
    expect($array['status'])->toBe('pending');
    expect($array['from_account'])->toBeNull();
    expect($array['to_account'])->toBeNull();
    expect($array['asset_code'])->toBeNull();
    expect($array['amount'])->toBeNull();
    expect($array['fee'])->toBeNull();
    expect($array['reference'])->toBeNull();
    expect($array['created_at'])->toBeNull();
    expect($array['completed_at'])->toBeNull();
    expect($array['metadata'])->toBe([]);
});
