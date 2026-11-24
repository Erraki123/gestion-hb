<?php
namespace App\Console\Commands;
use App\Services\ProspectDiscoveryService;
use Illuminate\Console\Command;
class DiscoverProspects extends Command {
    protected $signature='prospects:discover {--dry-run : Preview without database writes} {--city= : Target an allowed city} {--sector= : Target an allowed OSM sector}';
    protected $description='Collect candidate businesses from explicitly approved sources (no outreach)';
    public function handle(ProspectDiscoveryService $service): int {
        $city=$this->option('city');
        $sector=$this->option('sector');
        try {
            $result=$service->discover((bool)$this->option('dry-run'),$city === null ? null : trim((string)$city),$sector === null ? null : trim((string)$sector));
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }
        $this->line(json_encode($result, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
        return empty($result['errors']) ? self::SUCCESS : self::FAILURE;
    }
}
