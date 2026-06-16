<?php

declare(strict_types=1);

namespace App\Domain\ISO8583\Services;

use App\Domain\ISO8583\Enums\MessageTypeIndicator;
use App\Domain\ISO8583\Enums\ResponseCode;
use App\Domain\ISO8583\ValueObjects\Iso8583Message;
use Illuminate\Support\Str;

final class AuthorizationHandler
{
    /**
     * Process a 0100 authorization request and return a 0110 response.
     */
    public function handleRequest(Iso8583Message $request): Iso8583Message
    {
        $responseMti = $request->getMti()->responseType();
        if ($responseMti === null) {
            $responseMti = MessageTypeIndicator::AUTH_RESPONSE;
        }

        $response = new Iso8583Message($responseMti);

        // Echo back key fields from request
        $echoFields = [2, 3, 4, 7, 11, 12, 13, 41, 42, 49];
        foreach ($echoFields as $field) {
            $value = $request->getField($field);
            if ($value !== null) {
                $response->setField($field, $value);
            }
        }

        // Determine response code
        $responseCode = $this->authorize($request);
        $response->setField(39, $responseCode->value);

        // Generate authorization code if approved
        if ($responseCode->isApproved()) {
            $response->setField(38, strtoupper(substr(Str::random(6), 0, 6)));
        }

        return $response;
    }

    private function authorize(Iso8583Message $request): ResponseCode
    {
        $pan = $request->getField(2);
        $amountStr = $request->getField(4);

        if ($pan === null || $amountStr === null) {
            return ResponseCode::INVALID_TRANSACTION;
        }

        // Basic validation
        if (strlen($pan) < 13 || strlen($pan) > 19) {
            return ResponseCode::INVALID_TRANSACTION;
        }

        // Amount in cents — convert to dollars for limit check
        $amount = ((int) $amountStr) / 100.0;

        if ($amount <= 0) {
            return ResponseCode::INVALID_TRANSACTION;
        }

        // Default: approve (actual card lookup and spend limit checks
        // would be wired to CardIssuance domain in production)
        return ResponseCode::APPROVED;
    }
}
