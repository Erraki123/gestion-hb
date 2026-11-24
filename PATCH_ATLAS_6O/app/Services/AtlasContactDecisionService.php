<?php
namespace App\Services;

use App\Models\DiscoveredProspect;
use Illuminate\Support\Facades\DB;

final class AtlasContactDecisionService
{
    public function fingerprint(array $suggestion): string
    {
        return hash('sha256', json_encode([
            trim((string)($suggestion['phone'] ?? '')),
            trim((string)($suggestion['email'] ?? '')),
            trim((string)($suggestion['website'] ?? '')),
            trim((string)($suggestion['evidence_url'] ?? '')),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    public function decision(DiscoveredProspect $candidate, array $suggestion): ?object
    {
        $record = DB::table('atlas_contact_decisions')
            ->where('discovered_prospect_id', $candidate->id)->first();
        if (!$record || !hash_equals((string)$record->fingerprint, $this->fingerprint($suggestion))) {
            return null;
        }
        return $record;
    }

    public function record(DiscoveredProspect $candidate, array $suggestion, string $decision, int $userId): void
    {
        if (!in_array($decision, ['reviewed','rejected'], true)) {
            throw new \InvalidArgumentException('Invalid decision');
        }
        DB::table('atlas_contact_decisions')->updateOrInsert(
            ['discovered_prospect_id' => $candidate->id],
            [
                'fingerprint' => $this->fingerprint($suggestion),
                'decision' => $decision,
                'reviewed_by' => $userId,
                'reviewed_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
