<?php

declare(strict_types=1);

namespace App\Domain\ISO8583\Services;

use App\Domain\ISO8583\Enums\MessageTypeIndicator;
use App\Domain\ISO8583\Enums\ResponseCode;
use App\Domain\ISO8583\ValueObjects\Iso8583Message;

final class ReversalHandler
{
    /**
     * Process a 0400 reversal request and return a 0410 response.
     */
    public function handleRequest(Iso8583Message $request): Iso8583Message
    {
        $responseMti = $request->getMti()->responseType();
        if ($responseMti === null) {
            $responseMti = MessageTypeIndicator::REVERSAL_RESPONSE;
        }

        $response = new Iso8583Message($responseMti);

        // Echo back key fields from request
        $echoFields = [2, 3, 4, 7, 11, 12, 13, 38, 41, 42, 49];
        foreach ($echoFields as $field) {
            $value = $request->getField($field);
            if ($value !== null) {
                $response->setField($field, $value);
            }
        }

        // Determine response code
        $responseCode = $this->reverse($request);
        $response->setField(39, $responseCode->value);

        return $response;
    }

    private function reverse(Iso8583Message $request): ResponseCode
    {
        $pan = $request->getField(2);
        $amountStr = $request->getField(4);

        if ($pan === null || $amountStr === null) {
            return ResponseCode::INVALID_TRANSACTION;
        }

        // Approve reversal if original fields are present
        return ResponseCode::APPROVED;
    }
}
