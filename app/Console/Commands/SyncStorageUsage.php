<?php

namespace App\Console\Commands;

use App\Models\Agency;
use Illuminate\Console\Command;

class SyncStorageUsage extends Command
{
    protected $signature = 'storage:sync {--agency= : Sync specific agency by ID}';

    protected $description = 'Recalculate storage usage for all agencies based on actual media file sizes';

    public function handle()
    {
        $agencyId = $this->option('agency');

        if ($agencyId) {
            $agencies = Agency::where('id', $agencyId)->get();
            if ($agencies->isEmpty()) {
                $this->error("Agency with ID {$agencyId} not found.");
                return 1;
            }
        } else {
            $agencies = Agency::all();
        }

        $this->info("Syncing storage usage for {$agencies->count()} agencies...");

        $bar = $this->output->createProgressBar($agencies->count());
        $bar->start();

        foreach ($agencies as $agency) {
            $oldUsage = $agency->storage_used;
            $newUsage = $agency->recalculateStorageUsed();

            if ($oldUsage !== $newUsage) {
                $this->line('');
                $this->info("Agency '{$agency->name}': {$this->formatBytes($oldUsage)} → {$this->formatBytes($newUsage)}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->line('');
        $this->info('Storage sync complete.');

        return 0;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 1) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' B';
    }
}
