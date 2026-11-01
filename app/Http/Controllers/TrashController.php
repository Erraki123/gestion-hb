<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Product;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TrashController extends Controller
{
    private const TYPES = [
        'clients'    => ['model' => Client::class,   'label' => 'Clients'],
        'products'   => ['model' => Product::class,  'label' => 'Produits'],
        'categories' => ['model' => Category::class, 'label' => 'Catégories'],
        'quotes'     => ['model' => Quote::class,    'label' => 'Devis'],
        'orders'     => ['model' => Order::class,    'label' => 'Commandes'],
        'invoices'   => ['model' => Invoice::class,  'label' => 'Factures (brouillons)'],
        'users'      => ['model' => User::class,     'label' => 'Commerciaux'],
    ];

    public function index(Request $request)
    {
        $type = $request->input('type', 'clients');
        abort_unless(isset(self::TYPES[$type]), 404);

        $model  = self::TYPES[$type]['model'];
        $counts = collect(self::TYPES)->map(fn ($t) => $this->query($t['model'])->count());

        $items = $this->query($model)->latest('deleted_at')->paginate(15)->withQueryString();

        $deletedBy = ActivityLog::where('action', 'deleted')
            ->where('subject_type', $model)
            ->whereIn('subject_id', $items->pluck('id'))
            ->latest('id')->get()->unique('subject_id')->keyBy('subject_id');

        $descr = $items->getCollection()->mapWithKeys(fn ($m) => [$m->id => $this->describe($type, $m)]);

        return view('trash.index', [
            'types' => self::TYPES, 'type' => $type, 'counts' => $counts,
            'items' => $items, 'deletedBy' => $deletedBy, 'descr' => $descr,
        ]);
    }

    public function restore(string $type, int $id)
    {
        abort_unless(isset(self::TYPES[$type]), 404);

        $model = $this->query(self::TYPES[$type]['model'])->findOrFail($id);

        // Cohérence : le parent doit exister avant de restaurer l'enfant
        $problem = null;

        if ($type === 'clients' && $model->commercial()->first()?->trashed()) {
            $problem = 'Restaurez d\'abord le commercial propriétaire de ce client.';
        }

        if (in_array($type, ['quotes', 'orders', 'invoices'], true) && $model->client()->first()?->trashed()) {
            $problem = 'Restaurez d\'abord le client de ce document.';
        }

        if ($type === 'invoices' && $model->type === 'facture' && $model->order_id
            && Invoice::where('order_id', $model->order_id)->where('type', 'facture')
                ->where('status', '!=', 'annulee')->where('id', '!=', $model->id)->exists()) {
            $problem = 'Cette commande possède déjà une autre facture active.';
        }

        if ($problem) {
            return back()->withErrors(['restore' => $problem]);
        }

        DB::transaction(function () use ($model, $type) {
            $model->restore();

            if ($type === 'users') {
                $model->update(['is_active' => false]); // réactivation volontaire par l'admin
            }
        });

        return redirect()->route('trash.index', ['type' => $type])->with('success', $type === 'users'
            ? 'Compte restauré en état désactivé : activez-le depuis la page Commerciaux.'
            : 'Élément restauré.');
    }

    private function query(string $model)
    {
        $q = $model::onlyTrashed();

        if ($model === User::class) {
            $q->where('role', 'commercial');
        }

        return $q;
    }

    private function describe(string $type, $m): array
    {
        $money = fn ($v) => number_format((float) $v, 2, ',', ' ') . ' DH';

        return match ($type) {
            'clients'    => [$m->name, trim(($m->company ?: '') . ' · ' . ($m->city ?: ''), ' ·')],
            'products'   => [$m->name, 'SKU ' . $m->sku],
            'categories' => [$m->name, ''],
            'quotes'     => [$m->number, $money($m->total_ttc)],
            'orders'     => [$m->number, $money($m->total_ttc)],
            'invoices'   => [$m->displayNumber(), $money($m->total_ttc)],
            'users'      => [$m->name, $m->email],
        };
    }
}