<?php

namespace App\Console\Commands;

use App\Services\HnbChromePdfQueue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

/** The same render.cjs that already works in the owner's Hostinger SSH session. */
final class HnbProcessQueuedPdf extends Command
{
    protected $signature = 'hnb:pdf-process {--limit=1 : Maximum PDFs per invocation}';
    protected $description = 'Generate original HN BLOOM Chromium PDFs via CLI, outside the restricted web PHP process';

    public function handle(): int
    {
        $dir = HnbChromePdfQueue::directory();
        if (! is_dir($dir)) {
            $this->info('No pending PDF requests.');
            return self::SUCCESS;
        }

        $node = '/opt/alt/alt-nodejs22/root/usr/bin/node';
        $render = base_path('tools/pdf-chrome/render.cjs');
        $home = getenv('HOME') ?: null;
        if (! $home && function_exists('posix_getpwuid') && function_exists('posix_geteuid')) {
            $home = (posix_getpwuid(posix_geteuid()) ?: [])['dir'] ?? null;
        }
        $home = $home ?: '/home/u451019543';
        $chrome = rtrim($home, '/').'/.cache/hnb-chrome/chrome-headless-shell/linux-157.0.8103.78/chrome-headless-shell-linux64/chrome-headless-shell';
        if (! is_file($node) || ! is_file($render) || ! is_file($chrome)) {
            $this->error('Node, Chrome or PDF renderer missing in CLI environment.');
            return self::FAILURE;
        }

        // Shared hosts can run overlapping cron; never start two browsers at once.
        $lockPath = $dir.DIRECTORY_SEPARATOR.'.worker.lock';
        $lock = fopen($lockPath, 'c+');
        if (! $lock || ! flock($lock, LOCK_EX | LOCK_NB)) {
            if ($lock) fclose($lock);
            $this->line('Another PDF worker is running.');
            return self::SUCCESS;
        }
        @chmod($lockPath, 0600);
        try {
            $this->cleanup($dir);
            $jobs = glob($dir.DIRECTORY_SEPARATOR.'*.queued') ?: [];
            usort($jobs, static fn ($a, $b) => filemtime($a) <=> filemtime($b));
            $limit = min(3, max(1, (int) $this->option('limit')));
            $handled = 0;
            foreach ($jobs as $job) {
                if ($handled >= $limit) break;
                $key = basename($job, '.queued');
                if (! preg_match('/^[a-f0-9]{64}$/D', $key)) continue;
                $base = $dir.DIRECTORY_SEPARATOR.$key;
                $html = $base.'.html';
                $pdf = $base.'.pdf';
                if (! is_file($html)) {
                    File::put($base.'.failed', 'HTML source missing');
                    File::delete($job);
                    $this->warn("PDF job $key: source missing");
                    continue;
                }
                $handled++;
                try {
                    $process = new Process([$node, $render, $html, $pdf], base_path(), [
                        'HNB_CHROME_PATH' => $chrome,
                        'FONTCONFIG_FILE' => $home.'/.config/hnb-fontconfig/fonts.conf',
                        'HOME' => $home,
                    ]);
                    $process->setTimeout(110);
                    $process->run();
                    $magic = is_file($pdf) ? file_get_contents($pdf, false, null, 0, 5) : null;
                    if (! $process->isSuccessful() || ! is_file($pdf) || filesize($pdf) <= 500 || $magic !== '%PDF-') {
                        $error = trim($process->getErrorOutput().' '.$process->getOutput());
                        throw new \RuntimeException($error ?: 'Chromium CLI did not produce a valid PDF');
                    }
                    @chmod($pdf, 0600);
                    File::delete([$job, $base.'.failed']);
                    $this->info('PDF generated: '.$key);
                } catch (\Throwable $e) {
                    File::delete($pdf);
                    File::put($base.'.failed', substr($e->getMessage(), 0, 2000));
                    @chmod($base.'.failed', 0600);
                    File::delete($job);
                    Log::error('HN BLOOM PDF CLI generation failed', [
                        'job' => $key, 'error' => substr($e->getMessage(), 0, 1000),
                    ]);
                    $this->error('PDF failed: '.$key.' (see storage/logs/laravel.log)');
                }
            }
            if ($handled === 0) $this->line('No pending PDF requests.');
            return self::SUCCESS;
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function cleanup(string $dir): void
    {
        // Private cached HTML/PDF files: expire after 48 hours, no database changes.
        $cutoff = time() - (48 * 3600);
        foreach (['html', 'pdf', 'failed', 'queued'] as $ext) {
            foreach (glob($dir.DIRECTORY_SEPARATOR.'*.'.$ext) ?: [] as $p) {
                if (is_file($p) && filemtime($p) < $cutoff) File::delete($p);
            }
        }
    }
}
