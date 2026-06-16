<?php

declare(strict_types=1);

namespace App\Infrastructure\Logging;

use Monolog\Formatter\JsonFormatter;
use Monolog\LogRecord;

class StructuredJsonFormatter extends JsonFormatter
{
    /**
     * Format a log record with structured context.
     */
    public function format(LogRecord $record): string
    {
        $data = [
            'timestamp'  => $record->datetime->format('Y-m-d\TH:i:s.uP'),
            'level'      => $record->level->getName(),
            'message'    => $record->message,
            'channel'    => $record->channel,
            'hostname'   => gethostname() ?: 'unknown',
            'request_id' => $record->extra['request_id'] ?? null,
            'trace_id'   => $record->extra['trace_id'] ?? null,
            'span_id'    => $record->extra['span_id'] ?? null,
            'domain'     => $record->extra['domain'] ?? null,
        ];

        // Merge context data
        if (! empty($record->context)) {
            $data['context'] = $record->context;
        }

        // Merge extra data (excluding already-extracted fields)
        $extraKeys = ['request_id', 'trace_id', 'span_id', 'domain'];
        $extra = array_diff_key($record->extra, array_flip($extraKeys));
        if (! empty($extra)) {
            $data['extra'] = $extra;
        }

        // Remove null values for cleaner output
        $data = array_filter($data, fn ($value) => $value !== null);

        return $this->toJson($data) . "\n";
    }
}
