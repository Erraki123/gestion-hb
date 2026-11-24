<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Quote;
use Illuminate\Http\Request;

/** Contextual, read-only customer questions. No raw SQL and no privileged queries. */
class CrmConversationContextService
{
    private const KEY = 'hnbloom_ai_last_client';

    public function extractClientName(string $question): ?string
    {
        $q = trim($question);
        if (preg_match('/(?:\bclient(?:e)?\b|\bcustomer\b|\bزبون\b|\bالزبون\b)\s+(?:(?:qui|nomm[eé]|appel[eé]|s’appelle|s\x27appelle|de|du|la|le|qui s\x27appelle)\s+){0,2}[«"“]?([\pL\pN][\pL\pN .\x27-]{1,65})/iu', $q, $m)) {
            $candidate=trim($m[1], " .\t\n\r\0\x0B\"»“”");
            $candidate=preg_split('/\s+(?:et|avec|puis|dont|ses|leurs|factures?|devis|commandes?)\b/iu', $candidate)[0];
            $candidate=trim($candidate);
            if (preg_match('/^(?:qui|est|sont|a|ont|dans|en|les|des|tous|toutes|infos?|information|factures?|devis|commandes?|stock)$/iu', $candidate)) return null;
            return mb_strlen($candidate) >= 2 ? $candidate : null;
        }
        return null;
    }

    public function followupKind(string $question): ?string
    {
        $q=mb_strtolower(trim($question));
        $reference='/(?:\b(?:ses|son|sa|leurs|his|her|their)\b|ديالو|ديالها|ديالهم|تاعو|الخاصة به)/u';
        if (!preg_match($reference, $q)) return null;
        if (preg_match('/\b(?:factures?|impay[eé]es?|paiements?|invoices?)\b|فواتير|الفواتير/u', $q)) return 'invoices';
        if (preg_match('/\bdevis\b|عروض|العروض/u', $q)) return 'quotes';
        if (preg_match('/\b(?:informations?|d[eé]tails?|coordonn[eé]es?)\b|المعلومات/u', $q)) return 'client';
        return null;
    }

    public function handle(Request $request, string $question): ?string
    {
        $name=$this->extractClientName($question);
        $kind=$this->followupKind($question);
        if ($name === null && $kind === null) return null;

        if ($name !== null) {
            // Global owner scope is mandatory for commercial users.
            $pattern='%'.addcslashes($name, '%_\\').'%';
            $matches=Client::query()->where(function ($query) use ($pattern) {
                $query->where('name','like',$pattern)->orWhere('company','like',$pattern);
            })->orderBy('id')->limit(4)->get(['id','name','company','city','status']);
            if ($matches->isEmpty()) {
                $request->session()->forget(self::KEY);
                return 'Je ne trouve aucun client accessible correspondant à « '.$name.' ». Essayez son nom exact.';
            }
            if ($matches->count() > 1) {
                $request->session()->forget(self::KEY);
                return "Plusieurs clients correspondent à « {$name} » :\n".$matches->map(fn($c)=>'• '.$c->name.($c->company?' — '.$c->company:''))->implode("\n")."\nPrécisez un nom complet pour éviter toute confusion.";
            }
            $client=$matches->first();
            $request->session()->put(self::KEY, ['user_id'=>$request->user()->id,'client_id'=>$client->id,'at'=>time()]);
            if (preg_match('/\b(?:factures?|impay[eé]es?|invoices?)\b|فواتير/u', $question)) return $this->invoiceSummary($client, $question);
            if (preg_match('/\bdevis\b|عروض/u', $question)) return $this->quoteSummary($client);
            return $this->clientSummary($client);
        }

        $context=$request->session()->get(self::KEY);
        if (!is_array($context) || ($context['user_id']??null)!==$request->user()->id || time()-(int)($context['at']??0)>1800) {
            $request->session()->forget(self::KEY);
            return 'De quel client parlez-vous ? Indiquez son nom pour que je consulte son dossier.';
        }
        // Re-check ownership on every request; never trust session client_id alone.
        $client=Client::query()->find($context['client_id']);
        if (!$client) {
            $request->session()->forget(self::KEY);
            return 'Ce client n’est plus accessible depuis votre compte.';
        }
        return match($kind) {
            'invoices'=>$this->invoiceSummary($client,$question),
            'quotes'=>$this->quoteSummary($client),
            default=>$this->clientSummary($client),
        };
    }

    private function clientSummary(Client $client): string
    {
        return 'Client : '.$client->name
            .($client->company?' — '.$client->company:'')
            .($client->city?' | Ville : '.$client->city:'')
            .' | Statut : '.($client->status ?: 'non renseigné')
            ."\nVous pouvez maintenant demander « Et ses devis ? » ou « Et ses factures impayées ? ».";
    }

    private function invoiceSummary(Client $client, string $question): string
    {
        $onlyOpen=(bool) preg_match('/\b(?:impay[eé]es?|non pay[eé]es?|restantes?|en attente)\b|غير المؤداة|ما تخلصوش/u', mb_strtolower($question));
        $rows=Invoice::query()->where('client_id',$client->id)->where('type','facture')
            ->when($onlyOpen,fn($q)=>$q->whereIn('status',Invoice::OPEN))
            ->orderByDesc('id')->limit(8)->get(['id','number','status','total_ttc','amount_paid']);
        if ($rows->isEmpty()) return 'Aucune facture '.($onlyOpen?'ouverte ':'').'accessible pour '.$client->name.'.';
        $items=$rows->map(function($i) {
            $balance=max(0,(float)$i->total_ttc-(float)$i->amount_paid);
            return '• '.($i->number ?: '#'.$i->id).' — '.$i->status.' — '.number_format((float)$i->total_ttc,2,',',' ').' DH (reste indicatif : '.number_format($balance,2,',',' ').' DH)';
        })->implode("\n");
        return 'Factures '.($onlyOpen?'ouvertes ':'').'de '.$client->name." (8 max) :\n".$items."\nLe reste est indicatif : les avoirs et chèques éventuels doivent être vérifiés sur la facture.";
    }

    private function quoteSummary(Client $client): string
    {
        $rows=Quote::query()->where('client_id',$client->id)->orderByDesc('id')->limit(8)->get(['id','number','status','total_ttc']);
        if ($rows->isEmpty()) return 'Aucun devis accessible pour '.$client->name.'.';
        return 'Devis de '.$client->name." (8 max) :\n".$rows->map(fn($q)=>'• '.($q->number ?: '#'.$q->id).' — '.$q->status.' — '.number_format((float)$q->total_ttc,2,',',' ').' DH')->implode("\n");
    }
}
