<?php

declare(strict_types=1);

namespace App\Domain\ISO20022\Services;

use Exception;
use LibXMLError;
use SimpleXMLElement;

final class MessageValidator
{
    public function __construct(
        private readonly MessageRegistry $registry,
    ) {
    }

    /**
     * @return array{valid: bool, errors: array<string>}
     */
    public function validate(string $xml): array
    {
        $errors = [];

        // 1. Well-formed XML
        libxml_use_internal_errors(true);
        $doc = @simplexml_load_string($xml);
        $xmlErrors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors(false);

        if ($doc === false || $xmlErrors !== []) {
            $errorMessages = array_map(
                static fn (LibXMLError $e): string => trim($e->message),
                $xmlErrors
            );

            if ($errorMessages === []) {
                $errorMessages = ['XML is not well-formed'];
            }

            return ['valid' => false, 'errors' => $errorMessages];
        }

        // 2. Recognized namespace
        $messageType = $this->registry->detectMessageType($xml);

        if ($messageType === null) {
            return [
                'valid'  => false,
                'errors' => ['Unrecognized ISO 20022 namespace'],
            ];
        }

        // 3. Enabled message family
        /** @var array<string> $enabledFamilies */
        $enabledFamilies = (array) config('iso20022.enabled_families', ['pain', 'pacs', 'camt']);
        $family = explode('.', $messageType)[0];

        if (! in_array($family, $enabledFamilies, true)) {
            $errors[] = "Message family '{$family}' is not enabled";
        }

        // 4. Message size
        $maxSizeKb = (int) config('iso20022.max_message_size_kb', 512);
        $sizeKb = (int) ceil(strlen($xml) / 1024);

        if ($sizeKb > $maxSizeKb) {
            $errors[] = "Message size {$sizeKb}KB exceeds maximum {$maxSizeKb}KB";
        }

        // 5. Required fields per message type
        $fieldErrors = $this->validateRequiredFields($xml, $messageType);
        $errors = array_merge($errors, $fieldErrors);

        return [
            'valid'  => $errors === [],
            'errors' => $errors,
        ];
    }

    /**
     * @return array<string>
     */
    private function validateRequiredFields(string $xml, string $messageType): array
    {
        $errors = [];

        try {
            $doc = new SimpleXMLElement($xml);
        } catch (Exception) {
            return ['Failed to parse XML for field validation'];
        }

        $ns = $this->registry->getNamespace($messageType);

        if ($ns === null) {
            return [];
        }

        $root = $doc->children($ns);

        switch ($messageType) {
            case 'pain.001':
                $grpHdr = $root->CstmrCdtTrfInitn->GrpHdr ?? null;

                if ($grpHdr === null || (string) $grpHdr->MsgId === '') {
                    $errors[] = 'Required field MsgId is missing';
                }

                if ($grpHdr !== null && (string) $grpHdr->NbOfTxs === '') {
                    $errors[] = 'Required field NbOfTxs is missing';
                }

                break;

            case 'pacs.008':
                $grpHdr = $root->FIToFICstmrCdtTrf->GrpHdr ?? null;

                if ($grpHdr === null || (string) $grpHdr->MsgId === '') {
                    $errors[] = 'Required field MsgId is missing';
                }

                if ($grpHdr !== null && (string) $grpHdr->NbOfTxs === '') {
                    $errors[] = 'Required field NbOfTxs is missing';
                }

                break;

            case 'pacs.002':
                $grpHdr = $root->FIToFIPmtStsRpt->GrpHdr ?? null;

                if ($grpHdr === null || (string) $grpHdr->MsgId === '') {
                    $errors[] = 'Required field MsgId is missing';
                }

                break;
        }

        return $errors;
    }
}
