<?php

namespace App\Jobs;

use App\Models\Export as ExportModel;
use App\Models\ExportSetting;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Process\Process;

class Export implements ShouldQueue
{
    use Queueable;

    /**
     * The number of seconds the job can run before timing out.
     * Set to 0 to disable timeout (GoExport CLI handles its own timeouts).
     *
     * @var int
     */
    public $timeout = 0;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

    public function __construct(
        public string $service,
        public string $userId,
        public string $videoId,
        public string $videoAspectRatio,
        public string $videoResolution,
        public bool $videoOutro,
        public ?int $exportId = null
    ) {
        $this->onQueue('exports');
    }

    public function handle(): void
    {
        $export = $this->findExport();

        if ($export) {
            $export->update(['status' => 'in_progress']);
        }

        try {
            $fileName = "{$this->userId}.{$this->videoId} " . Uuid::uuid4()->toString() . ".mp4";
            $outputPath = storage_path("app/public/exports/{$fileName}");
            $publicUrl = config('app.url') . "/storage/exports/{$fileName}";
            $returnVar = $this->executeCommand($outputPath, $export);

            if ($export && $returnVar === 0) {
                $export->update([
                    'status' => 'completed',
                    'file_path' => $publicUrl,
                ]);
                Log::info("Export completed", ['export_id' => $export->id]);
            } else {
                $export?->update(['status' => 'failed']);
                Log::error("Export failed", ['export_id' => $export?->id, 'code' => $returnVar]);
            }
        } catch (\Exception $e) {
            $export?->update(['status' => 'failed']);
            Log::error("Export exception", ['export_id' => $export?->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    private function findExport(): ?ExportModel
    {
        return $this->exportId
            ? ExportModel::find($this->exportId)
            : ExportModel::where([
                'service' => $this->service,
                'userId' => $this->userId,
                'videoId' => $this->videoId,
                'videoAspectRatio' => $this->videoAspectRatio,
                'videoResolution' => $this->videoResolution,
            ])->latest()->first();
    }

    private function executeCommand(string $outputPath, ?ExportModel $export = null): int
    {
        $cli = ExportSetting::getCliSettings();

        // Determine if outro should be used (force or requested)
        $useOutro = $cli['force_outro'] || $this->videoOutro;

        // Build command arguments array for Symfony Process
        $args = [
            base_path('bin/goexport/GoExport_CLI'),
            '--service=' . $this->service,
            '--aspect_ratio=' . $this->videoAspectRatio,
            '--resolution=' . $this->videoResolution,
            '--movie-id=' . $this->videoId,
            '--owner-id=' . $this->userId,
            '--output-path=' . $outputPath,
            '--auto-edit',
            '--no-input',
            '--json',
            '--console',
            '--x11grab-display=:99',
            '--pulse-audio=auto_null.monitor',
            '--skip-resolution-check',
            '--ffmpeg-linux-override',
            '{ffmpeg} -y -f x11grab -video_size {width}x{height} -framerate 60 -draw_mouse 0 -i {display} -f pulse -i {pulse_audio} -ac 2 -c:v libx264 -preset ultrafast -tune zerolatency -crf 23 -pix_fmt yuv420p -profile:v baseline -level 4.2 -bf 0 -refs 1 -threads 0 -c:a aac -b:a 160k -ar 44100 -movflags +faststart \'{output}\''
        ];

        // Add OBS settings if configured
        if (!empty($cli['obs_websocket_address'])) {
            $args[] = '--obs-websocket-address=' . $cli['obs_websocket_address'];
        }
        if (!empty($cli['obs_websocket_port'])) {
            $args[] = '--obs-websocket-port=' . $cli['obs_websocket_port'];
        }
        if (!empty($cli['obs_websocket_password'])) {
            $args[] = '--obs-websocket-password=' . $cli['obs_websocket_password'];
        }
        if (!empty($cli['obs_fps'])) {
            $args[] = '--obs-fps=' . $cli['obs_fps'];
        }
        if ($cli['obs_no_overwrite']) {
            $args[] = '--obs-no-overwrite';
        }
        if ($cli['obs_required']) {
            $args[] = '--obs-required';
        }

        // Add timeout settings
        if (isset($cli['load_timeout'])) {
            $args[] = '--load-timeout=' . (string) $cli['load_timeout'];
        }
        if (isset($cli['video_timeout'])) {
            $args[] = '--video-timeout=' . (string) $cli['video_timeout'];
        }

        // Add outro flag
        if ($useOutro) {
            $args[] = '--use-outro';
        }

        // Environment variables - inherit parent env and set up proper X11/Chrome environment
        $env = $_ENV + $_SERVER; // Inherit current environment

        // X11 and display settings
        $env['DISPLAY'] = ':99';  // Use Xorg display with window manager, not VNC display

        // Ensure clean PATH
        if (!isset($env['PATH']) || empty($env['PATH'])) {
            $env['PATH'] = '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin';
        }

        // Log the command being executed for debugging
        $formattedArgs = array_map(function ($arg) {
            if (strpos($arg, '=') !== false) {
                [$param, $value] = explode('=', $arg, 2);
                $value = str_replace('"', '\\"', $value);
                return $param . '="' . $value . '"';
            }
            return $arg;
        }, $args);
        Log::info("Executing export command", ['command' => implode(' ', $formattedArgs)]);

        // Create and run the process
        $process = new Process($args, null, $env);
        $process->setTimeout(3600); // 1 hour timeout
        $process->run();

        $processOutput = $process->getOutput() . $process->getErrorOutput();
        $returnVar = $process->getExitCode();

        if ($export) {
            $export->update(['process_output' => $processOutput ?: 'N/A']);
        }

        return $returnVar;
    }

    public function failed(\Throwable $exception): void
    {
        ExportModel::find($this->exportId)?->update(['status' => 'failed']);
        Log::error("Export job failed", ['export_id' => $this->exportId, 'error' => $exception->getMessage()]);
    }
}
