<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\ISO8583\Enums\MessageTypeIndicator;
use App\Domain\ISO8583\Services\FieldDefinitions;
use App\Domain\ISO8583\Services\MessageCodec;
use App\Domain\ISO8583\ValueObjects\Iso8583Message;
use Illuminate\Console\Command;

class BenchmarkPaymentRailsCommand extends Command
{
    protected $signature = 'benchmark:payment-rails {--count=1000 : Number of iterations}';

    protected $description = 'Benchmark payment rail processing speed';

    public function handle(): int
    {
        $count = (int) $this->option('count');

        // ISO 8583 codec benchmark
        $this->info("ISO 8583 encode/decode x{$count}...");
        $codec = new MessageCodec(new FieldDefinitions());

        $msg = new Iso8583Message(MessageTypeIndicator::AUTH_REQUEST);
        $msg->setField(2, '4111111111111111');
        $msg->setField(3, '000000');
        $msg->setField(4, '000000010000');
        $msg->setField(11, '123456');
        $msg->setField(41, 'TERM0001');
        $msg->setField(42, 'MERCH00000001');
        $msg->setField(49, '840');

        $start = microtime(true);
        for ($i = 0; $i < $count; $i++) {
            $encoded = $codec->encode($msg);
            $codec->decode($encoded);
        }
        $iso8583Time = microtime(true) - $start;
        $iso8583Rate = $count / $iso8583Time;

        $this->table(['Benchmark', 'Count', 'Time (s)', 'Ops/second'], [
            ['ISO 8583 encode+decode', $count, number_format($iso8583Time, 3), number_format($iso8583Rate, 0)],
        ]);

        return self::SUCCESS;
    }
}
