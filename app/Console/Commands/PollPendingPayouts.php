<?php

namespace App\Console\Commands;

use App\Models\Payout;
use App\Services\PayoutService;
use Illuminate\Console\Command;
use Throwable;

class PollPendingPayouts extends Command
{
    protected $signature = 'payouts:poll-pending
                            {--limit=50 : Maximum number of pending payouts to check per run}
                            {--min-age=1 : Minimum age in minutes before polling a payout}';

    protected $description = 'Poll PawaPay API for status updates on all pending payouts';

    public function handle(PayoutService $payoutService): int
    {
        $limit = (int) $this->option('limit');
        $minAge = (int) $this->option('min-age');

        $payouts = Payout::query()
            ->where('status', Payout::STATUS_PENDING)
            ->whereNotNull('pawapay_reference')
            ->where('created_at', '<=', now()->subMinutes($minAge))
            ->latest()
            ->limit($limit)
            ->get();

        if ($payouts->isEmpty()) {
            $this->info('No pending payouts to poll.');

            return self::SUCCESS;
        }

        $this->info("Polling {$payouts->count()} pending payout(s)...");

        $updated = 0;

        foreach ($payouts as $payout) {
            try {
                $refreshed = $payoutService->processPayoutStatus($payout);

                if ($refreshed->status !== Payout::STATUS_PENDING) {
                    $updated++;
                    $this->line("  ✓ Payout #{$payout->id} ({$payout->phone_number}) → {$refreshed->status}");
                } else {
                    $this->line("  · Payout #{$payout->id} still pending.");
                }
            } catch (Throwable $e) {
                $this->warn("  ✗ Payout #{$payout->id}: {$e->getMessage()}");
            }
        }

        $this->info("Done. {$updated} payout(s) updated to terminal status.");

        return self::SUCCESS;
    }
}
