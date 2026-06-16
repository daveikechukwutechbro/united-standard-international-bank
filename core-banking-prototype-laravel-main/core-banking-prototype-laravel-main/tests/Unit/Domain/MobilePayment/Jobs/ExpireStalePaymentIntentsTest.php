<?php

declare(strict_types=1);

use App\Domain\MobilePayment\Jobs\ExpireStalePaymentIntents;

describe('ExpireStalePaymentIntents Job', function (): void {
    it('is queueable', function (): void {
        $job = new ExpireStalePaymentIntents();

        expect($job)->toBeInstanceOf(Illuminate\Contracts\Queue\ShouldQueue::class);
    });

    it('can be instantiated', function (): void {
        $job = new ExpireStalePaymentIntents();

        expect($job)->toBeInstanceOf(ExpireStalePaymentIntents::class);
    });
});
