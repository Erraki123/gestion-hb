<?php
namespace App\Console\Commands;
use App\Services\ProspectDiscoveryService;
use Illuminate\Console\Command;
class DiscoverProspects extends Command {
    protected $signature='prospects:discover {--dry-run : Preview without database writes}';
    protected $description='Collect candidate businesses from explicitly approved sources (no outreach)';
    public function handle(ProspectDiscoveryService $service): int {
        $result=$service->discover((bool)$this->option('dry-run'));
        $this->line(json_encode($result, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
        return empty($result['errors']) ? self::SUCCESS : self::FAILURE;
    }
}
