<?php

namespace App\Jobs;

use App\Models\Export;
use App\Models\ExportSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PurgeOldExports implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $minutes = ExportSetting::getPurgeAfterDays();

        if ($minutes <= 0) {
            return;
        }

        $cutoff = now()->subMinutes($minutes);

        $exports = Export::where('created_at', '<', $cutoff)->get();

        foreach ($exports as $export) {
            // Delete the file if it exists
            if ($export->file_path && Storage::disk('public')->exists($export->file_path)) {
                Storage::disk('public')->delete($export->file_path);
            }

            // Delete the record
            $export->delete();

            Log::info("Purged old export: {$export->id} (Created: {$export->created_at})");
        }

        if ($exports->count() > 0) {
            Log::info("Purged {$exports->count()} old exports older than {$minutes} minutes.");
        }
    }
}
