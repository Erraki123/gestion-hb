<?php

namespace App\Console\Commands;

use App\Services\ProspectCsvImportService;
use Illuminate\Console\Command;

class ImportDiscoveredProspects extends Command
{
    protected $signature = 'prospects:import-csv {filename : Fichier dans storage/app/prospect-imports} {--source= : Identifiant de la source autorisée} {--dry-run : Aucun changement en base} {--limit=300 : Nombre maximum de lignes}';
    protected $description = 'Importer des entreprises professionnelles depuis un CSV autorisé pour revue (aucun contact automatique)';

    public function handle(ProspectCsvImportService $service): int
    {
        try {
            $source=(string)$this->option('source');
            $result=$service->import((string)$this->argument('filename'),$source,(bool)$this->option('dry-run'),(int)$this->option('limit'));
            $this->line(json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }
    }
}
