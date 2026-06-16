<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\OpenBanking\Services\ConsentEnforcementService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceConsent
{
    public function __construct(
        private readonly ConsentEnforcementService $enforcement,
    ) {
    }

    /**
     * @param string $permission The required consent permission
     */
    public function handle(Request $request, Closure $next, string $permission = 'ReadAccountsBasic'): Response
    {
        $consentId = $request->header('X-Consent-ID');
        if ($consentId === null) {
            return response()->json([
                'success' => false,
                'error'   => ['code' => 'CONSENT_MISSING', 'message' => 'X-Consent-ID header is required'],
            ], 403);
        }

        $tppId = $request->attributes->get('tpp_id');
        $user = $request->user();

        if ($tppId === null || $user === null) {
            return response()->json([
                'success' => false,
                'error'   => ['code' => 'UNAUTHORIZED', 'message' => 'TPP and user authentication required'],
            ], 401);
        }

        $accountId = $request->route('accountId');

        if (! $this->enforcement->validateAccess($tppId, $user->id, $permission, $accountId)) {
            return response()->json([
                'success' => false,
                'error'   => ['code' => 'CONSENT_INVALID', 'message' => 'Consent is invalid, expired, or does not grant the required permission'],
            ], 403);
        }

        $this->enforcement->logAccess($consentId, $tppId, $request->path(), $request->ip());
        $request->attributes->set('consent_id', $consentId);

        return $next($request);
    }
}
