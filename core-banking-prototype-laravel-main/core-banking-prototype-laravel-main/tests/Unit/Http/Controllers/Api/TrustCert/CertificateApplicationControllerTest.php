<?php

declare(strict_types=1);

use App\Domain\TrustCert\Services\CertificateAuthorityService;
use App\Http\Controllers\Api\TrustCert\CertificateApplicationController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Tests\UnitTestCase;

uses(UnitTestCase::class);

beforeEach(function (): void {
    $this->certificateAuthority = Mockery::mock(CertificateAuthorityService::class);
    Cache::flush();
});

function makeCertAppController($test): CertificateApplicationController
{
    return new CertificateApplicationController(
        $test->certificateAuthority,
    );
}

function certAppUserRequest(string $uri, string $method = 'GET', array $data = []): Request
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

describe('CertificateApplicationController create', function (): void {
    it('creates a new certificate application', function (): void {
        $controller = makeCertAppController($this);

        $request = certAppUserRequest('/api/v1/trustcert/applications', 'POST', [
            'target_level' => 'verified',
        ]);

        $response = $controller->create($request);
        $data = $response->getData(true);

        expect($response->getStatusCode())->toBe(201)
            ->and($data['success'])->toBeTrue()
            ->and($data['data']['target_level'])->toBe('verified')
            ->and($data['data']['status'])->toBe('pending')
            ->and($data['data']['id'])->toStartWith('app_');
    });

    it('prevents duplicate active applications', function (): void {
        $controller = makeCertAppController($this);

        // Create first application
        $request1 = certAppUserRequest('/api/v1/trustcert/applications', 'POST', [
            'target_level' => 'verified',
        ]);
        $controller->create($request1);

        // Try to create second
        $request2 = certAppUserRequest('/api/v1/trustcert/applications', 'POST', [
            'target_level' => 'high',
        ]);
        $response = $controller->create($request2);

        expect($response->getStatusCode())->toBe(409);
    });
});

describe('CertificateApplicationController show', function (): void {
    it('returns application by ID', function (): void {
        $controller = makeCertAppController($this);

        // Create application first
        $createRequest = certAppUserRequest('/api/v1/trustcert/applications', 'POST', [
            'target_level' => 'verified',
        ]);
        $createResponse = $controller->create($createRequest);
        $appId = $createResponse->getData(true)['data']['id'];

        // Show it
        $response = $controller->show($appId, certAppUserRequest("/api/v1/trustcert/applications/{$appId}"));
        $data = $response->getData(true);

        expect($data['success'])->toBeTrue()
            ->and($data['data']['id'])->toBe($appId);
    });

    it('returns 404 for non-existent application', function (): void {
        $controller = makeCertAppController($this);

        $response = $controller->show('nonexistent', certAppUserRequest('/api/v1/trustcert/applications/nonexistent'));

        expect($response->getStatusCode())->toBe(404);
    });
});

describe('CertificateApplicationController currentApplication', function (): void {
    it('returns null when no active application', function (): void {
        $controller = makeCertAppController($this);

        $response = $controller->currentApplication(certAppUserRequest('/api/v1/trustcert/applications/current'));
        $data = $response->getData(true);

        expect($data['success'])->toBeTrue()
            ->and($data['data'])->toBeNull();
    });

    it('returns active application', function (): void {
        $controller = makeCertAppController($this);

        // Create one
        $controller->create(certAppUserRequest('/api/v1/trustcert/applications', 'POST', [
            'target_level' => 'basic',
        ]));

        // Check current
        $response = $controller->currentApplication(certAppUserRequest('/api/v1/trustcert/applications/current'));
        $data = $response->getData(true);

        expect($data['success'])->toBeTrue()
            ->and($data['data'])->not->toBeNull()
            ->and($data['data']['target_level'])->toBe('basic');
    });
});

describe('CertificateApplicationController submit', function (): void {
    it('submits a draft application', function (): void {
        $controller = makeCertAppController($this);

        // Create
        $createResponse = $controller->create(certAppUserRequest('/api/v1/trustcert/applications', 'POST', [
            'target_level' => 'verified',
        ]));
        $appId = $createResponse->getData(true)['data']['id'];

        // Submit
        $response = $controller->submit($appId, certAppUserRequest("/api/v1/trustcert/applications/{$appId}/submit", 'POST'));
        $data = $response->getData(true);

        expect($data['success'])->toBeTrue()
            ->and($data['data']['status'])->toBe('in_review')
            ->and($data['data']['submitted_at'])->not->toBeNull();
    });

    it('rejects submitting non-draft application', function (): void {
        $controller = makeCertAppController($this);

        // Create and submit
        $createResponse = $controller->create(certAppUserRequest('/api/v1/trustcert/applications', 'POST', [
            'target_level' => 'verified',
        ]));
        $appId = $createResponse->getData(true)['data']['id'];
        $controller->submit($appId, certAppUserRequest("/api/v1/trustcert/applications/{$appId}/submit", 'POST'));

        // Try to submit again
        $response = $controller->submit($appId, certAppUserRequest("/api/v1/trustcert/applications/{$appId}/submit", 'POST'));

        expect($response->getStatusCode())->toBe(422);
    });
});

describe('CertificateApplicationController cancel', function (): void {
    it('cancels a draft application', function (): void {
        $controller = makeCertAppController($this);

        // Create
        $createResponse = $controller->create(certAppUserRequest('/api/v1/trustcert/applications', 'POST', [
            'target_level' => 'verified',
        ]));
        $appId = $createResponse->getData(true)['data']['id'];

        // Cancel
        $response = $controller->cancel($appId, certAppUserRequest("/api/v1/trustcert/applications/{$appId}/cancel", 'POST'));
        $data = $response->getData(true);

        expect($data['success'])->toBeTrue()
            ->and($data['data']['status'])->toBe('cancelled');
    });
});

describe('CertificateApplicationController uploadDocuments', function (): void {
    it('uploads a document to a draft application', function (): void {
        $controller = makeCertAppController($this);

        // Create
        $createResponse = $controller->create(certAppUserRequest('/api/v1/trustcert/applications', 'POST', [
            'target_level' => 'verified',
        ]));
        $appId = $createResponse->getData(true)['data']['id'];

        // Upload document
        $response = $controller->uploadDocuments($appId, certAppUserRequest(
            "/api/v1/trustcert/applications/{$appId}/documents",
            'POST',
            ['document_type' => 'id_front', 'file_name' => 'passport.pdf'],
        ));
        $data = $response->getData(true);

        expect($response->getStatusCode())->toBe(201)
            ->and($data['success'])->toBeTrue()
            ->and($data['data']['document_type'])->toBe('id_front')
            ->and($data['data']['id'])->toStartWith('doc_');
    });
});

describe('TrustCert application routes', function (): void {
    it('has applications create route defined', function (): void {
        $route = app('router')->getRoutes()->getByName('mobile.trustcert.applications.create');
        expect($route)->not->toBeNull();
    });

    it('has applications current route defined', function (): void {
        $route = app('router')->getRoutes()->getByName('mobile.trustcert.applications.current');
        expect($route)->not->toBeNull();
    });
});
