<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\LogsActivity;
class Quote extends Model
{
    use SoftDeletes , LogsActivity;

    protected $fillable = [
        'number', 'client_id', 'commercial_id', 'status', 'issue_date', 'valid_until',
        'subtotal_ht', 'tax_rate', 'tax_amount', 'total_ttc', 'terms', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'issue_date'  => 'date',
            'valid_until' => 'date',
        ];
    }

    // ⭐ Le commercial ne voit que ses devis
    protected static function booted(): void
    {
        static::addGlobalScope('owner', function (Builder $builder) {
            $user = auth()->user();
            if ($user && $user->role === 'commercial') {
                $builder->where('quotes.commercial_id', $user->id);
            }
        });
    }

    public function client()     { return $this->belongsTo(Client::class)->withTrashed(); }
    public function commercial() { return $this->belongsTo(User::class, 'commercial_id')->withTrashed(); }
    public function items()      { return $this->hasMany(QuoteItem::class); }
    public function order()      { return $this->hasOne(Order::class); }

    /**
     * Un devis est « ouvert » tant qu'il n'est ni refusé ni transformé en commande.
     * (les anciens statuts « envoyé » et « accepté » restent considérés comme ouverts)
     */
    public function isOpen(): bool
    {
        return in_array($this->status, ['brouillon', 'envoye', 'accepte'], true)
            && ! $this->order;
    }

    public function isEditable(): bool { return $this->isOpen(); }
    public function canConvert(): bool { return $this->isOpen(); }
    public function canRefuse(): bool  { return $this->isOpen(); }

    /** Expiré = automatique : la date de validité est dépassée (aucun bouton). */
    public function isExpired(): bool
    {
        return $this->valid_until !== null
            && $this->valid_until->copy()->endOfDay()->isPast();
    }

    /**
     * Étape unique affichée dans la page « Commandes » :
     * devis | expire | refuse | commande | livraison_partielle | livre | facture | annulee
     */
    public function stage(): string
    {
        $order = $this->order;

        if ($order) {
            if ($order->status === 'annulee') {
                return 'annulee';
            }

            if ($order->invoice) {
                return 'facture';
            }

            return match ($order->deliveryStatus()) {
                'livree'   => 'livre',
                'partielle' => 'livraison_partielle',
                default    => 'commande',
            };
        }

        if ($this->status === 'refuse') {
            return 'refuse';
        }

        return $this->isExpired() ? 'expire' : 'devis';
    }
}
