<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\HnbAgentRoutineService;
use Illuminate\Console\Command;

class HnbAgentRoutinesRefresh extends Command
{
    protected $signature = 'hnb:routines:refresh {--dry-run : Aucune écriture en base}';
    protected $description = 'Scan quotidien local des actions CRM par utilisateur : aucun e-mail, WhatsApp ni approbation';

    public function handle(HnbAgentRoutineService $service): int
    {
        $users = 0; $total = 0; $priority = 0;
        User::query()->where('is_active', true)->whereIn('role', ['admin', 'commercial'])
            ->orderBy('id')->chunkById(50, function ($group) use ($service, &$users, &$total, &$priority): void {
                foreach ($group as $user) {
                    $result = $service->scanDaily($user, (bool) $this->option('dry-run'));
                    $users++;
                    $total += $result['total'];
                    $priority += $result['priority'];
                }
            });
        $this->info("Comptes: {$users}; actions ouvertes: {$total}; prioritaires: {$priority}.");
        $this->comment($this->option('dry-run') ? 'DRY RUN : aucun enregistrement.'
            : 'Résumé enregistré localement. Aucun message ni opération sensible envoyé.');
        return self::SUCCESS;
    }
}
