<?php

declare(strict_types=1);

use App\Infrastructure\Logging\StructuredJsonFormatter;
use Monolog\Level;
use Monolog\LogRecord;

describe('StructuredJsonFormatter', function () {
    it('formats log record as structured JSON', function () {
        $formatter = new StructuredJsonFormatter();
        $record = new LogRecord(
            datetime: new DateTimeImmutable(),
            channel: 'test',
            level: Level::Info,
            message: 'Test message',
            context: ['key' => 'value'],
            extra: ['request_id' => 'req-123', 'trace_id' => 'trace-456'],
        );

        $output = $formatter->format($record);
        $decoded = json_decode($output, true);

        expect($decoded)->toBeArray();
        expect($decoded)->toHaveKey('timestamp');
        expect($decoded)->toHaveKey('level');
        expect($decoded)->toHaveKey('message');
        expect($decoded)->toHaveKey('channel');
        expect($decoded)->toHaveKey('hostname');
        expect($decoded['level'])->toBe('INFO');
        expect($decoded['message'])->toBe('Test message');
        expect($decoded['channel'])->toBe('test');
        expect($decoded['request_id'])->toBe('req-123');
        expect($decoded['trace_id'])->toBe('trace-456');
        expect($decoded['context'])->toBe(['key' => 'value']);
    });

    it('includes domain context when present', function () {
        $formatter = new StructuredJsonFormatter();
        $record = new LogRecord(
            datetime: new DateTimeImmutable(),
            channel: 'test',
            level: Level::Warning,
            message: 'Domain warning',
            context: [],
            extra: ['domain' => 'Account'],
        );

        $output = $formatter->format($record);
        $decoded = json_decode($output, true);

        expect($decoded['domain'])->toBe('Account');
    });

    it('excludes null values from output', function () {
        $formatter = new StructuredJsonFormatter();
        $record = new LogRecord(
            datetime: new DateTimeImmutable(),
            channel: 'test',
            level: Level::Debug,
            message: 'Minimal log',
            context: [],
            extra: [],
        );

        $output = $formatter->format($record);
        $decoded = json_decode($output, true);

        expect($decoded)->not->toHaveKey('request_id');
        expect($decoded)->not->toHaveKey('trace_id');
        expect($decoded)->not->toHaveKey('span_id');
        expect($decoded)->not->toHaveKey('domain');
        expect($decoded)->not->toHaveKey('context');
    });

    it('includes span_id when present', function () {
        $formatter = new StructuredJsonFormatter();
        $record = new LogRecord(
            datetime: new DateTimeImmutable(),
            channel: 'test',
            level: Level::Info,
            message: 'With span',
            context: [],
            extra: ['span_id' => 'span-789'],
        );

        $output = $formatter->format($record);
        $decoded = json_decode($output, true);

        expect($decoded['span_id'])->toBe('span-789');
    });

    it('preserves extra fields beyond standard ones', function () {
        $formatter = new StructuredJsonFormatter();
        $record = new LogRecord(
            datetime: new DateTimeImmutable(),
            channel: 'test',
            level: Level::Info,
            message: 'Extra fields',
            context: [],
            extra: ['request_id' => 'req-1', 'custom_field' => 'custom_value'],
        );

        $output = $formatter->format($record);
        $decoded = json_decode($output, true);

        expect($decoded['request_id'])->toBe('req-1');
        expect($decoded['extra'])->toHaveKey('custom_field');
        expect($decoded['extra']['custom_field'])->toBe('custom_value');
    });

    it('outputs valid JSON ending with newline', function () {
        $formatter = new StructuredJsonFormatter();
        $record = new LogRecord(
            datetime: new DateTimeImmutable(),
            channel: 'test',
            level: Level::Info,
            message: 'Newline test',
            context: [],
            extra: [],
        );

        $output = $formatter->format($record);

        expect($output)->toEndWith("\n");
        expect(json_decode(trim($output), true))->toBeArray();
    });

    it('formats timestamp in ISO 8601 with microseconds', function () {
        $formatter = new StructuredJsonFormatter();
        $record = new LogRecord(
            datetime: new DateTimeImmutable('2026-02-12T10:00:00.123456Z'),
            channel: 'test',
            level: Level::Info,
            message: 'Timestamp test',
            context: [],
            extra: [],
        );

        $output = $formatter->format($record);
        $decoded = json_decode($output, true);

        expect($decoded['timestamp'])->toContain('2026-02-12T10:00:00');
    });
});
