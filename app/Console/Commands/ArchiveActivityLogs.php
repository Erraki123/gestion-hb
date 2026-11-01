<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Services\ActivityLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ArchiveActivityLogs extends Command
{
    protected $signature = 'activity:archive {--months=24 : Archiver les entrées plus anciennes que N mois (minimum 6)}';

    protected $description = 'Exporte puis supprime les anciennes entrées du journal d\'activité';

    public function handle(): int
    {
        $months = max(6, (int) $this->option('months'));
        $limit  = now()->subMonths($months);

        $total = ActivityLog::where('created_at', '<', $limit)->count();

        if ($total === 0) {
            $this->info('Rien à archiver.');

            return self::SUCCESS;
        }

        $disk = Storage::disk('local');
        $disk->makeDirectory('activity-archives');

        $rel  = 'activity-archives/activity-' . $limit->format('Ymd') . '-' . now()->format('YmdHis') . '.jsonl';
        $path = $disk->path($rel);

        $h = fopen($path, 'w');
        if (! $h) {
            $this->error('Impossible de créer le fichier d\'archive.');

            return self::FAILURE;
        }

        $written = 0;
        $maxId   = 0;

        ActivityLog::where('created_at', '<', $limit)->orderBy('id')
            ->chunkById(1000, function ($rows) use ($h, &$written, &$maxId) {
                foreach ($rows as $r) {
                    fwrite($h, json_encode($r->toArray(), JSON_UNESCAPED_UNICODE) . "\n");
                    $written++;
                    $maxId = max($maxId, $r->id);
                }
            });

        fclose($h);

        // On ne supprime QUE si l'export est complet
        if ($written !== $total) {
            $this->error("Export incomplet ({$written}/{$total}) : aucune suppression.");

            return self::FAILURE;
        }

        // Suppression de masse volontaire (le modèle bloque la suppression unitaire)
        $deleted = ActivityLog::where('created_at', '<', $limit)
            ->where('id', '<=', $maxId)->toBase()->delete();

        ActivityLogger::log('backup', "Archivage du journal : {$deleted} entrée(s) exportée(s) vers {$rel}", null, [
            'data' => ['fichier' => $rel, 'lignes' => $deleted, 'avant' => $limit->format('Y-m-d')],
        ]);

        $this->info("{$deleted} entrée(s) archivée(s) dans storage/app/private/{$rel}");

        return self::SUCCESS;
    }
}
