<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Product;
use App\Models\Quote;
use Illuminate\Support\Str;

/** Read-only, allowlisted queries; model global scopes enforce commercial ownership. */
class CrmAiContextService
{
    public function fetch(string $domain, string $search = ''): array
    {
        $search = trim($search);
        $pattern = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search).'%';
        $limited = $search !== '';
        $records = match ($domain) {
            'products' => Product::query()
                ->when($limited, fn ($q) => $q->where(fn ($sub) => $sub->where('name', 'like', $pattern)->orWhere('sku', 'like', $pattern)))
                ->orderBy('id', 'desc')->limit(12)->get(['id','name','sku','stock_quantity','min_stock','sale_price','unit','is_active'])
                ->map(fn ($p) => ['reference'=>$p->sku,'produit'=>$p->name,'stock'=>(float)$p->stock_quantity,'seuil'=>(float)$p->min_stock,'unite'=>$p->unit,'prix_vente'=>(float)$p->sale_price,'actif'=>(bool)$p->is_active])->all(),
            'clients' => Client::query()
                ->when($limited, fn ($q) => $q->where(fn ($sub) => $sub->where('name','like',$pattern)->orWhere('company','like',$pattern)->orWhere('city','like',$pattern)))
                ->orderBy('id', 'desc')->limit(12)->get(['id','name','company','city','status'])
                ->map(fn ($c) => ['id'=>$c->id,'client'=>$c->name,'societe'=>$c->company,'ville'=>$c->city,'statut'=>$c->status])->all(),
            'quotes' => Quote::query()
                ->when($limited, fn ($q) => $q->where('number','like',$pattern))
                ->orderBy('id', 'desc')->limit(12)->get(['id','number','status','total_ttc','client_id'])
                ->map(fn ($q) => ['numero'=>$q->number,'statut'=>$q->status,'total_ttc'=>(float)$q->total_ttc])->all(),
            'orders' => Order::query()
                ->when($limited, fn ($q) => $q->where('number','like',$pattern))
                ->orderBy('id', 'desc')->limit(12)->get(['id','number','status','total_ttc','client_id'])
                ->map(fn ($o) => ['numero'=>$o->number,'statut'=>$o->status,'total_ttc'=>(float)$o->total_ttc])->all(),
            'invoices' => Invoice::query()
                ->when($limited, fn ($q) => $q->where('number','like',$pattern))
                ->orderBy('id', 'desc')->limit(12)->get(['id','number','status','total_ttc','amount_paid','type'])
                ->map(fn ($i) => ['numero'=>$i->number,'type'=>$i->type,'statut'=>$i->status,'total_ttc'=>(float)$i->total_ttc,'paye'=>(float)$i->amount_paid])->all(),
            default => [],
        };
        return ['domaine'=>$domain, 'recherche'=>$search, 'limite'=>12, 'resultats'=>$records,
            'information'=>'Résultats limités aux enregistrements autorisés et 12 lignes. Aucun prix d’achat, coût ou identifiant secret.'];
    }
}
