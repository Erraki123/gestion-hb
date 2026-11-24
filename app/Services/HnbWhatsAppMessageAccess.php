<?php
namespace App\Services;

use App\Models\Client;
use App\Models\User;
use App\Models\WhatsAppInboundMessage;
use Illuminate\Database\Eloquent\Builder;

/** Only currently unambiguous, linked client numbers are visible to a commercial. */
class HnbWhatsAppMessageAccess
{
    public function query(User $user): Builder
    {
        $query = WhatsAppInboundMessage::query();
        if ($user->isAdmin()) return $query;
        abort_unless($user->isCommercial(), 403);
        $pairs = [];
        $duplicates = [];
        foreach (Client::withoutGlobalScope('owner')->whereNotNull('phone')
            ->select(['id','commercial_id','phone'])->cursor() as $client) {
            $phone = WhatsAppService::normalize($client->phone);
            if (!$phone || isset($duplicates[$phone])) continue;
            if (isset($pairs[$phone])) {
                unset($pairs[$phone]);
                $duplicates[$phone] = true;
                continue;
            }
            $pairs[$phone] = [(int) $client->id, (int) $client->commercial_id];
        }
        $allowed = array_filter($pairs, fn ($pair) => $pair[1] === (int) $user->id);
        if (!$allowed) return $query->whereRaw('1 = 0');
        // Exact client/sender pairs; chunk predicates to keep expression depth bounded.
        return $query->where(function (Builder $outer) use ($allowed) {
            foreach (array_chunk($allowed, 100, true) as $chunk) {
                $outer->orWhere(function (Builder $group) use ($chunk) {
                    foreach ($chunk as $phone => $pair) {
                        $group->orWhere(fn (Builder $q) => $q->where('client_id', $pair[0])->where('sender', (string) $phone));
                    }
                });
            }
        });
    }
}
