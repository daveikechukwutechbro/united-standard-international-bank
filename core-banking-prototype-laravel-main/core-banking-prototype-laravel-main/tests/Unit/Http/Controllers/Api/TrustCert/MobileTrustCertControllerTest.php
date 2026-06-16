<?php

declare(strict_types=1);

use App\Domain\TrustCert\Enums\CertificateStatus;
use App\Domain\TrustCert\Services\CertificateAuthorityService;
use App\Domain\TrustCert\ValueObjects\Certificate;
use App\Http\Controllers\Api\TrustCert\MobileTrustCertController;
use App\Models\User;
use Illuminate\Http\Request;
use Tests\UnitTestCase;

uses(UnitTestCase::class);

beforeEach(function (): void {
    $this->certificateAuthority = Mockery::mock(CertificateAuthorityService::class);
});

function makeTrustCertController($test): MobileTrustCertController
{
    return new MobileTrustCertController(
        $test->certificateAuthority,
    );
}

function trustCertUserRequest(string $uri, string $method = 'GET', array $data = []): Request
{
    if ($method === 'POST') {
        $request = Request::create($uri, $method, $data, [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));
    } else {
        $request = Request::create($uri, $method, $data);
    }
    $user = Mockery::mock(User::class)->makePartial();
    $user->id = 1;
    $request->setUserResolver(fn () => $user);

    return $request;
}

describe('MobileTrustCertController current', function (): void {
    it('returns unknown trust level when no certificate exists', function (): void {
        $this->certificateAuthority->shouldReceive('getCertificateBySubject')
            ->with('user:1')
            ->andReturn(null);

        $controller = makeTrustCertController($this);

        $response = $controller->current(trustCertUserRequest('/api/v1/trustcert/current'));
        $data = $response->getData(true);

        expect($data['success'])->toBeTrue()
            ->and($data['data'])->toBeNull();
    });

    it('returns certificate info when user has certificate', function (): void {
        $cert = new Certificate(
            certificateId: 'cert-123',
            subjectId: 'user:1',
            subject: ['name' => 'Test User'],
            publicKey: 'pk-test',
            signature: 'sig-test',
            validFrom: new DateTimeImmutable('-1 day'),
            validUntil: new DateTimeImmutable('+365 days'),
            status: CertificateStatus::ACTIVE,
            extensions: ['trust_level' => 'verified'],
        );

        $this->certificateAuthority->shouldReceive('getCertificateBySubject')
            ->with('user:1')
            ->andReturn($cert);

        $controller = makeTrustCertController($this);

        $response = $controller->current(trustCertUserRequest('/api/v1/trustcert/current'));
        $data = $response->getData(true);

        expect($data['success'])->toBeTrue()
            ->and($data['data']['trust_level'])->toBe('verified')
            ->and($data['data']['is_valid'])->toBeTrue()
            ->and($data['data']['certificate'])->toBeArray()
            ->and($data['data']['certificate']['certificate_id'])->toBe('cert-123');
    });
});

describe('MobileTrustCertController requirements', function (): void {
    it('returns all trust levels with requirements', function (): void {
        $controller = makeTrustCertController($this);

        $response = $controller->requirements();
        $data = $response->getData(true);

        expect($data['success'])->toBeTrue()
            ->and($data['data'])->toBeArray()
            ->and(count($data['data']))->toBe(5);

        $levels = array_column($data['data'], 'level');
        expect($levels)->toContain(0)
            ->and($levels)->toContain(1)
            ->and($levels)->toContain(2)
            ->and($levels)->toContain(3)
            ->and($levels)->toContain(4);
    });

    it('includes limits for each trust level', function (): void {
        $controller = makeTrustCertController($this);

        $response = $controller->requirements();
        $data = $response->getData(true);

        $verified = collect($data['data'])->firstWhere('level', 2);
        expect($verified)->toHaveKeys(['dailyLimit', 'monthlyLimit', 'documents'])
            ->and($verified['requirements'])->toBeArray()
            ->and($verified['documents'])->toContain('id_front');
    });
});

describe('MobileTrustCertController requirementsByLevel', function (): void {
    it('returns specific level requirements', function (): void {
        $controller = makeTrustCertController($this);

        $response = $controller->requirementsByLevel('high');
        $data = $response->getData(true);

        expect($data['success'])->toBeTrue()
            ->and($data['data']['level'])->toBe(3)
            ->and($data['data']['documents'])->toContain('source_of_funds');
    });

    it('returns 404 for invalid trust level', function (): void {
        $controller = makeTrustCertController($this);

        $response = $controller->requirementsByLevel('nonexistent');

        expect($response->getStatusCode())->toBe(404);
    });

    it('accepts numeric level values', function (): void {
        $controller = makeTrustCertController($this);

        $response = $controller->requirementsByLevel('1');
        $data = $response->getData(true);

        expect($data['success'])->toBeTrue()
            ->and($data['data']['level'])->toBe(1)
            ->and($data['data']['name'])->toBe('Basic');
    });

    it('maps all numeric levels correctly', function (): void {
        $controller = makeTrustCertController($this);

        $expected = [['0', 'unknown'], ['1', 'basic'], ['2', 'verified'], ['3', 'high'], ['4', 'ultimate']];

        foreach ($expected as [$numeric, $name]) {
            $response = $controller->requirementsByLevel($numeric);
            $data = $response->getData(true);

            expect($data['success'])->toBeTrue()
                ->and($data['data']['level'])->toBe((int) $numeric);
        }
    });

    it('returns 404 for out-of-range numeric level', function (): void {
        $controller = makeTrustCertController($this);

        $response = $controller->requirementsByLevel('99');

        expect($response->getStatusCode())->toBe(404);
    });
});

describe('MobileTrustCertController limits', function (): void {
    it('returns transaction limits for all trust levels', function (): void {
        $controller = makeTrustCertController($this);

        $response = $controller->limits();
        $data = $response->getData(true);

        expect($data['success'])->toBeTrue()
            ->and($data['data'])->toBeArray()
            ->and(count($data['data']))->toBe(5);

        $basic = collect($data['data'])->firstWhere('level', 'basic');
        expect($basic['limits']['daily'])->toBe(500)
            ->and($basic['limits']['monthly'])->toBe(5000);
    });
});

describe('MobileTrustCertController checkLimit', function (): void {
    it('allows transaction within limits', function (): void {
        $cert = new Certificate(
            certificateId: 'cert-123',
            subjectId: 'user:1',
            subject: ['name' => 'Test User'],
            publicKey: 'pk-test',
            signature: 'sig-test',
            validFrom: new DateTimeImmutable('-1 day'),
            validUntil: new DateTimeImmutable('+365 days'),
            status: CertificateStatus::ACTIVE,
            extensions: ['trust_level' => 'verified'],
        );

        $this->certificateAuthority->shouldReceive('getCertificateBySubject')
            ->with('user:1')
            ->andReturn($cert);

        $controller = makeTrustCertController($this);

        $response = $controller->checkLimit(trustCertUserRequest(
            '/api/v1/trustcert/check-limit',
            'POST',
            ['amount' => 1000, 'transaction_type' => 'daily'],
        ));
        $data = $response->getData(true);

        expect($data['success'])->toBeTrue()
            ->and($data['data']['allowed'])->toBeTrue()
            ->and($data['data']['trust_level'])->toBe('verified');
    });

    it('rejects transaction exceeding limits', function (): void {
        $this->certificateAuthority->shouldReceive('getCertificateBySubject')
            ->with('user:1')
            ->andReturn(null);

        $controller = makeTrustCertController($this);

        $response = $controller->checkLimit(trustCertUserRequest(
            '/api/v1/trustcert/check-limit',
            'POST',
            ['amount' => 100, 'transaction_type' => 'daily'],
        ));
        $data = $response->getData(true);

        expect($data['success'])->toBeTrue()
            ->and($data['data']['allowed'])->toBeFalse()
            ->and($data['data']['trust_level'])->toBe('unknown')
            ->and($data['data']['limit'])->toBe(0);
    });
});

describe('TrustCert routes', function (): void {
    it('has trustcert current route defined', function (): void {
        $route = app('router')->getRoutes()->getByName('mobile.trustcert.current');
        expect($route)->not->toBeNull();
    });

    it('has trustcert requirements route defined', function (): void {
        $route = app('router')->getRoutes()->getByName('mobile.trustcert.requirements');
        expect($route)->not->toBeNull();
    });

    it('has trustcert limits route defined', function (): void {
        $route = app('router')->getRoutes()->getByName('mobile.trustcert.limits');
        expect($route)->not->toBeNull();
    });
});
