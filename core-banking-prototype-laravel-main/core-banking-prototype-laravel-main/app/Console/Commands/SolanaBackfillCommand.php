<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Account\Models\BlockchainAddress;
use App\Domain\Wallet\Helpers\SolanaAddressHelper;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Backfill Solana addresses for all existing users.
 *
 * Creates BlockchainAddress records so that solana:sync can register
 * them with the Helius webhook. Safe to run multiple times — uses
 * firstOrCreate to skip already-registered addresses.
 */
class SolanaBackfillCommand extends Command
{
    protected $signature = 'solana:backfill
        {--dry-run : Show count without creating records}';

    protected $description = 'Create Solana BlockchainAddress records for all existing users';

    public function handle(): int
    {
        $appKey = (string) config('app.key');

        if ($appKey === '') {
            $this->error('APP_KEY is not set. Cannot derive Solana addresses.');

            return self::FAILURE;
        }

        $query = User::query();
        $total = $query->count();

        if ($total === 0) {
            $this->warn('No users found.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $existing = BlockchainAddress::where('chain', 'solana')
                ->where('is_active', true)
                ->count();

            $this->info("Would backfill Solana addresses for {$total} users ({$existing} already exist).");

            return self::SUCCESS;
        }

        $this->info("Backfilling Solana addresses for {$total} users...");

        $created = 0;
        $skipped = 0;

        $query->select(['id', 'uuid'])->chunk(500, function ($users) use ($appKey, &$created, &$skipped): void {
            foreach ($users as $user) {
                $address = SolanaAddressHelper::deriveForUser($user->id, $appKey);

                $record = BlockchainAddress::firstOrCreate(
                    ['address' => $address, 'chain' => 'solana'],
                    [
                        'user_uuid'       => $user->uuid,
                        'public_key'      => $address,
                        'is_active'       => true,
                        'label'           => 'Primary Solana',
                        'derivation_path' => "m/44'/501'/0'/0'",
                    ]
                );

                if ($record->wasRecentlyCreated) {
                    $created++;
                } else {
                    $skipped++;
                }
            }
        });

        $this->info("Done. Created: {$created}, Already existed: {$skipped}.");
        $this->info('Run `php artisan solana:sync` to push addresses to Helius webhook.');

        return self::SUCCESS;
    }
}
