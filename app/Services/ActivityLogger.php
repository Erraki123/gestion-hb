<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Client;
use App\Models\ClientAttachment;
use App\Models\Commission;
use App\Models\CommissionPayout;
use App\Models\CommissionRule;
use App\Models\Delivery;
use App\Models\GoodsReceipt;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Quote;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Models\SupplierPayment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ActivityLogger
{
    public const SUBJECTS = [
        User::class             => 'Utilisateur',
        Client::class           => 'Client',
        ClientAttachment::class => 'Pièce jointe',
        Category::class         => 'Catégorie',
        Product::class          => 'Produit',
        Quote::class            => 'Devis',
        Order::class            => 'Commande',
        Delivery::class         => 'Bon de livraison',
        Supplier::class         => 'Fournisseur',
        PurchaseOrder::class    => 'Commande fournisseur',
        GoodsReceipt::class     => 'Réception fournisseur',
        SupplierInvoice::class  => 'Facture fournisseur',
        SupplierPayment::class  => 'Paiement fournisseur',
        Invoice::class          => 'Facture',
        \App\Models\CustomerReturn::class => 'Retour client',
        Payment::class          => 'Paiement',
        Commission::class       => 'Commission',
        CommissionRule::class   => 'Règle de commission',
        CommissionPayout::class => 'Versement de commission',
    ];

    public const ACTIONS = [
        'created'      => 'Création',
        'updated'      => 'Modification',
        'deleted'      => 'Suppression',
        'restored'     => 'Restauration',
        'login'        => 'Connexion',
        'logout'       => 'Déconnexion',
        'backup' => 'Sauvegarde',
        'login_failed' => 'Échec de connexion',
        'lockout'      => 'Blocage',
        'export'       => 'Export',
    ];

    /** Ne lève jamais d'exception : le journal ne doit pas bloquer l'opération métier */
    public static function log(
        string $action,
        string $description,
        ?Model $subject = null,
        array $properties = [],
        ?int $userId = null,
        ?string $userName = null
    ): void {
        try {
            $user = auth()->user();

            ActivityLog::create([
                'user_id'       => $userId ?? $user?->id,
                'user_name'     => $userName ?? $user?->name,
                'action'        => $action,
                'subject_type'  => $subject ? $subject::class : null,
                'subject_id'    => $subject?->getKey(),
                'subject_label' => $subject ? Str::limit(self::labelFor($subject), 150, '') : null,
                'description'   => Str::limit($description, 480, '…'),
                'properties'    => $properties ? self::clean($properties) : null,
                'ip'            => request()->ip(),
                'user_agent'    => Str::limit((string) request()->userAgent(), 250, ''),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public static function labelFor(Model $m): string
    {
        if (method_exists($m, 'displayNumber')) {
            return (string) $m->displayNumber();
        }

        foreach (['number', 'name', 'sku', 'original_name'] as $key) {
            if (! empty($m->getAttribute($key))) {
                return (string) $m->getAttribute($key);
            }
        }

        return class_basename($m) . ' #' . $m->getKey();
    }

    /** Retire tout secret, tronque les textes longs, normalise les dates */
    private static function clean(array $data): array
    {
        $out = [];

        foreach ($data as $k => $v) {
            if (is_string($k) && preg_match('/password|token|secret|remember/i', $k)) {
                continue;
            }

            if (is_array($v)) {
                $out[$k] = self::clean($v);
            } elseif ($v instanceof \DateTimeInterface) {
                $out[$k] = $v->format('Y-m-d H:i:s');
            } elseif (is_string($v)) {
                $out[$k] = Str::limit($v, 500, '…');
            } elseif (is_scalar($v) || $v === null) {
                $out[$k] = $v;
            } else {
                $out[$k] = '[objet]';
            }
        }

        return $out;
    }
}
