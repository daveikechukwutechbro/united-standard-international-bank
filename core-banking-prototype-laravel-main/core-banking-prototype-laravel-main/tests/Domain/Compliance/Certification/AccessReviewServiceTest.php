<?php

declare(strict_types=1);

use App\Domain\Compliance\Services\Certification\AccessReviewService;

describe('AccessReviewService', function () {
    beforeEach(function () {
        $this->service = new AccessReviewService();
        config(['compliance-certification.soc2.demo_mode' => true]);
    });

    it('generates access review report in demo mode', function () {
        $report = $this->service->runAccessReview();

        expect($report)->toBeArray()
            ->and($report)->toHaveKey('summary')
            ->and($report['summary'])->toHaveKey('privileged_user_count')
            ->and($report['summary'])->toHaveKey('total_roles')
            ->and($report['summary'])->toHaveKey('stale_account_count')
            ->and($report['summary'])->toHaveKey('dormant_token_count');
    });

    it('runs full access review in demo mode', function () {
        $result = $this->service->runAccessReview();

        expect($result)->toBeArray()
            ->and($result)->toHaveKey('privileged_users')
            ->and($result)->toHaveKey('permission_matrix')
            ->and($result)->toHaveKey('stale_accounts')
            ->and($result)->toHaveKey('dormant_tokens')
            ->and($result)->toHaveKey('recommendations');
    });

    it('returns permission matrix in demo mode', function () {
        $result = $this->service->runAccessReview();
        $matrix = $result['permission_matrix'];

        expect($matrix)->toBeArray()
            ->and($matrix)->not->toBeEmpty();
    });
});
