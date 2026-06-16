<?php

declare(strict_types=1);

namespace App\Domain\ISO8583\Services;

use App\Domain\ISO8583\Enums\MessageTypeIndicator;
use App\Domain\ISO8583\Enums\ResponseCode;
use App\Domain\ISO8583\ValueObjects\Iso8583Message;

final class SettlementHandler
{
    /**
     * Process a 0500 settlement request and return a 0510 response.
     */
    public function handleRequest(Iso8583Message $request): Iso8583Message
    {
        $responseMti = $request->getMti()->responseType();
        if ($responseMti === null) {
            $responseMti = MessageTypeIndicator::SETTLEMENT_RESPONSE;
        }

        $response = new Iso8583Message($responseMti);

        // Echo back key fields from request
        $echoFields = [2, 3, 4, 7, 11, 12, 13, 41, 42, 49, 70];
        foreach ($echoFields as $field) {
            $value = $request->getField($field);
            if ($value !== null) {
                $response->setField($field, $value);
            }
        }

        // Determine response code
        $responseCode = $this->settle($request);
        $response->setField(39, $responseCode->value);

        return $response;
    }

    private function settle(Iso8583Message $request): ResponseCode
    {
        $amountStr = $request->getField(4);

        if ($amountStr === null) {
            return ResponseCode::INVALID_TRANSACTION;
        }

        // Approve settlement
        return ResponseCode::APPROVED;
    }
}
