<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\OpenBanking\Services\TppRegistrationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateTppCertificate
{
    public function __construct(
        private readonly TppRegistrationService $tppService,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('openbanking.tpp_certificate_validation', true)) {
            return $next($request);
        }

        $clientId = $request->header('X-TPP-Client-ID');
        if ($clientId === null) {
            return response()->json([
                'success' => false,
                'error'   => ['code' => 'TPP_NOT_IDENTIFIED', 'message' => 'X-TPP-Client-ID header is required'],
            ], 403);
        }

        $tpp = $this->tppService->findByClientId($clientId);
        if ($tpp === null || $tpp->status !== 'active') {
            return response()->json([
                'success' => false,
                'error'   => ['code' => 'TPP_NOT_REGISTERED', 'message' => 'TPP is not registered or inactive'],
            ], 403);
        }

        $certificate = $request->header('X-TPP-Certificate', '');
        if ($certificate !== '' && ! $this->tppService->validateCertificate($certificate)) {
            return response()->json([
                'success' => false,
                'error'   => ['code' => 'CERTIFICATE_INVALID', 'message' => 'TPP certificate validation failed'],
            ], 403);
        }

        $request->attributes->set('tpp_id', $tpp->tpp_id);
        $request->attributes->set('tpp_registration', $tpp);

        return $next($request);
    }
}
