<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\TrustCert\Enums\CertificateStatus;
use App\Domain\TrustCert\Events\CertificateExpired;
use DateTimeImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use stdClass;

/**
 * Scheduled command to check and revoke expired TrustCert certificates.
 *
 * This command should run daily via the scheduler to:
 * 1. Identify certificates that have expired
 * 2. Update their status to EXPIRED
 * 3. Emit events for downstream processing (notifications, SBT revocation)
 * 4. Send renewal reminders for certificates expiring soon
 *
 * @example php artisan trustcert:check-expired
 * @example php artisan trustcert:check-expired --dry-run
 */
class CheckExpiredCertificatesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trustcert:check-expired
        {--dry-run : Show what would be done without making changes}
        {--reminder-days=30,14,7 : Days before expiry to send reminders}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for expired TrustCert certificates and process them';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $reminderDays = collect(explode(',', (string) $this->option('reminder-days')))
            ->map(fn ($day) => (int) trim($day))
            ->filter()
            ->values()
            ->toArray();

        $this->info('TrustCert Expiry Check - ' . ($isDryRun ? 'DRY RUN' : 'LIVE'));
        $this->newLine();

        $now = new DateTimeImmutable();
        $expiredCount = 0;
        $reminderCount = 0;

        // 1. Process expired certificates
        $this->info('Checking for expired certificates...');

        $expiredCertificates = $this->getExpiredCertificates($now);

        if ($expiredCertificates->isEmpty()) {
            $this->info('  No expired certificates found.');
        } else {
            $this->warn("  Found {$expiredCertificates->count()} expired certificate(s)");

            foreach ($expiredCertificates as $certificate) {
                $this->processExpiredCertificate($certificate, $isDryRun);
                $expiredCount++;
            }
        }

        $this->newLine();

        // 2. Send renewal reminders
        $this->info('Checking for certificates needing renewal reminders...');

        foreach ($reminderDays as $days) {
            $expiringCertificates = $this->getCertificatesExpiringIn($now, $days);

            if ($expiringCertificates->isNotEmpty()) {
                $this->info("  {$expiringCertificates->count()} certificate(s) expiring in {$days} days");

                foreach ($expiringCertificates as $certificate) {
                    $this->sendRenewalReminder($certificate, $days, $isDryRun);
                    $reminderCount++;
                }
            }
        }

        // 3. Summary
        $this->newLine();
        $this->table(
            ['Metric', 'Count'],
            [
                ['Expired certificates processed', $expiredCount],
                ['Renewal reminders sent', $reminderCount],
            ]
        );

        if ($isDryRun) {
            $this->warn('DRY RUN - No changes were made');
        }

        Log::info('TrustCert expiry check completed', [
            'expired_count'  => $expiredCount,
            'reminder_count' => $reminderCount,
            'dry_run'        => $isDryRun,
        ]);

        return self::SUCCESS;
    }

    /**
     * Get certificates that have expired.
     *
     * @return \Illuminate\Support\Collection<int, stdClass>
     */
    private function getExpiredCertificates(DateTimeImmutable $now): \Illuminate\Support\Collection
    {
        // Demo implementation - in production, query from database
        // return TrustCertificate::query()
        //     ->where('status', CertificateStatus::ACTIVE->value)
        //     ->where('valid_until', '<', $now)
        //     ->get();

        return collect(); // Empty for demo
    }

    /**
     * Get certificates expiring within specified days.
     *
     * @return \Illuminate\Support\Collection<int, stdClass>
     */
    private function getCertificatesExpiringIn(DateTimeImmutable $now, int $days): \Illuminate\Support\Collection
    {
        $targetDate = $now->modify("+{$days} days");
        $startOfDay = $targetDate->setTime(0, 0, 0);
        $endOfDay = $targetDate->setTime(23, 59, 59);

        // Demo implementation - in production, query from database
        // return TrustCertificate::query()
        //     ->where('status', CertificateStatus::ACTIVE->value)
        //     ->whereBetween('valid_until', [$startOfDay, $endOfDay])
        //     ->whereNull('renewal_reminder_sent_at')
        //     ->orWhere('renewal_reminder_sent_at', '<', $now->modify('-1 week'))
        //     ->get();

        return collect(); // Empty for demo
    }

    /**
     * Process an expired certificate.
     */
    private function processExpiredCertificate(stdClass $certificate, bool $isDryRun): void
    {
        $this->line("    Processing: {$certificate->certificate_id}");

        if ($isDryRun) {
            $this->line('      [DRY RUN] Would mark as EXPIRED');
            $this->line('      [DRY RUN] Would emit CertificateExpired event');
            $this->line('      [DRY RUN] Would revoke on-chain SBT (if applicable)');

            return;
        }

        DB::transaction(function () use ($certificate) {
            // In production with Eloquent model:
            // $certificate->update(['status' => CertificateStatus::EXPIRED->value, 'expired_at' => now()]);

            // Demo: Update status in-memory
            $certificate->status = CertificateStatus::EXPIRED->value;
            $certificate->expired_at = now();

            // Emit event for downstream processing
            Event::dispatch(new CertificateExpired(
                certificateId: $certificate->certificate_id,
                subjectId: $certificate->subject_id,
                expiredAt: now(),
            ));

            Log::info('TrustCert certificate expired', [
                'certificate_id' => $certificate->certificate_id,
                'subject_id'     => $certificate->subject_id,
            ]);
        });

        $this->info('      Marked as EXPIRED');
    }

    /**
     * Send renewal reminder for a certificate.
     */
    private function sendRenewalReminder(stdClass $certificate, int $daysUntilExpiry, bool $isDryRun): void
    {
        $this->line("    Reminder for: {$certificate->certificate_id} ({$daysUntilExpiry} days)");

        if ($isDryRun) {
            $this->line('      [DRY RUN] Would send renewal reminder notification');

            return;
        }

        // In production with Eloquent model:
        // $certificate->update(['renewal_reminder_sent_at' => now()]);

        // Demo: Update in-memory
        $certificate->renewal_reminder_sent_at = now();

        // In production, dispatch notification job
        // SendCertificateRenewalReminderJob::dispatch($certificate, $daysUntilExpiry);

        Log::info('TrustCert renewal reminder sent', [
            'certificate_id'    => $certificate->certificate_id,
            'days_until_expiry' => $daysUntilExpiry,
        ]);

        $this->info('      Reminder sent');
    }
}
