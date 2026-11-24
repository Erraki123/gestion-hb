<?php

use App\Models\Client;
use App\Models\Order;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schedule;

// Résumé unique, quotidien, réservé aux administrateurs actifs.
// L'option --dry-run ne transmet jamais de message au fournisseur e-mail.
Artisan::command('direction:alerts {--dry-run : Afficher le résumé sans envoyer}', function () {
    $pendingQuotes = Quote::query()
        ->where('requires_approval', true)
        ->where('approval_status', 'pending')
        ->whereIn('status', ['brouillon', 'envoye', 'accepte'])
        ->whereDoesntHave('order')
        ->count();

    $pendingOrders = Order::query()
        ->where('approval_status', 'pending')
        ->where('status', 'nouvelle')
        ->count();

    $lateFollowUps = Client::query()
        ->where('status', 'prospect')
        ->whereHas('prospectDetail', fn ($query) => $query
            ->where('do_not_contact', false)
            ->whereNotNull('next_follow_up_at')
            ->where('next_follow_up_at', '<=', now()))
        ->count();

    $total = $pendingQuotes + $pendingOrders + $lateFollowUps;
    $this->info("Devis en attente : {$pendingQuotes}");
    $this->info("Commandes en attente : {$pendingOrders}");
    $this->info("Relances à traiter : {$lateFollowUps}");

    if ($this->option('dry-run')) {
        $this->comment('MODE TEST : aucun e-mail envoyé.');
        return 0;
    }

    if (! config('direction_alerts.enabled', false)) {
        $this->comment('Notifications e-mail Direction désactivées.');
        return 0;
    }

    if ($total === 0) {
        $this->comment('Aucune action urgente. Pas d’e-mail envoyé.');
        return 0;
    }

    // Ne jamais envoyer à un compte Commercial ou à un administrateur désactivé.
    $emails = User::query()->where('role', 'admin')->where('is_active', true)
        ->whereNotNull('email')->pluck('email')->filter(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL))->unique();

    if ($emails->isEmpty()) {
        $this->warn('Aucun administrateur actif avec adresse e-mail valide.');
        return 0;
    }

    $base = rtrim((string) config('app.url'), '/');
    $body = "HN BLOOM — Actions prioritaires\n\n"
        . "Devis à valider : {$pendingQuotes}\n"
        . "Commandes à valider : {$pendingOrders}\n"
        . "Relances à traiter : {$lateFollowUps}\n\n"
        . "Validations : {$base}/admin/approvals\n"
        . "Prospection : {$base}/prospects\n\n"
        . "Message automatique destiné uniquement à la Direction.";

    foreach ($emails as $email) {
        Mail::raw($body, function ($message) use ($email, $total) {
            $message->to($email)->subject("HN BLOOM | {$total} action(s) à traiter");
        });
        $this->info("Résumé envoyé à : {$email}");
    }

    return 0;
})->purpose('Résumé quotidien des validations et relances pour la Direction');

Schedule::command('direction:alerts')
    ->dailyAt(config('direction_alerts.time', '08:30'))
    ->withoutOverlapping();
