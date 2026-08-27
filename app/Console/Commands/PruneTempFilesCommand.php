<?php

namespace App\Console\Commands;

use App\Services\MediaService;
use Illuminate\Console\Command;

class PruneTempFilesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media:prune-temp {--days=2 : Prune temp files older than this number of days}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune orphaned temporary media files from storage and database';

    /**
     * Execute the console command.
     */
    public function handle(MediaService $mediaService): int
    {
        $days = (int) $this->option('days');
        $this->info("Pruning temporary files older than {$days} days...");

        $count = $mediaService->pruneExpiredTempFiles($days);

        $this->info("Pruned {$count} orphaned temporary file(s).");

        return self::SUCCESS;
    }
}
