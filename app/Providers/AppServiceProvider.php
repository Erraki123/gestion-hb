<?php

namespace App\Providers;

use App\Events\CreditNoteIssued;
use App\Events\InvoiceCancelled;
use App\Events\InvoiceIssued;
use App\Events\PaymentCancelled;
use App\Events\PaymentRecorded;
use App\Services\ActivityLogger;
use App\Services\CommissionService;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $s = \App\Models\Setting::allValues();

        if ($s) {
            $map = [
                'commerce.company.name'    => 'company_name',
                'commerce.company.address' => 'company_address',
                'commerce.company.phone'   => 'company_phone',
                'commerce.company.email'   => 'company_email',
                'commerce.company.ice'     => 'company_ice',
                'commerce.invoice_footer'  => 'invoice_footer',
                'commerce.commission_base' => 'commission_base',
            ];

            foreach ($map as $configKey => $name) {
                if (isset($s[$name]) && $s[$name] !== '') {
                    config([$configKey => $s[$name]]);
                }
            }

            foreach (['default_tax_rate' => ['commerce.tax_rate', 'floatval'],
                      'payment_days' => ['commerce.payment_days', 'intval'],
                      'quote_validity_days' => ['commerce.quote_validity_days', 'intval']] as $name => [$configKey, $cast]) {
                if (isset($s[$name]) && $s[$name] !== '') {
                    config([$configKey => $cast($s[$name])]);
                }
            }
        }

        \Carbon\Carbon::setLocale(config('app.locale'));

        // Traductions JSON supplémentaires (resources/lang-extra/fr.json, etc.)
        app('translator')->getLoader()->addJsonPath(resource_path('lang-extra'));

        // HTTPS obligatoire en production uniquement
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        // Règles par défaut des mots de passe
        Password::defaults(function () {
            $rule = Password::min(10)->letters()->mixedCase()->numbers();

            return app()->isProduction() ? $rule->uncompromised() : $rule;
        });

        // Une erreur de commission ne doit jamais bloquer un paiement ou une facture
        $run = function (callable $fn) {
            try {
                $fn(app(CommissionService::class));
            } catch (\Throwable $e) {
                report($e);
            }
        };

        Event::listen(InvoiceIssued::class,    fn ($e) => $run(fn ($s) => $s->onInvoiceIssued($e->invoice)));
        Event::listen(InvoiceCancelled::class, fn ($e) => $run(fn ($s) => $s->onInvoiceCancelled($e->invoice)));
        Event::listen(CreditNoteIssued::class, fn ($e) => $run(fn ($s) => $s->onCreditNoteIssued($e->creditNote)));
        Event::listen(PaymentRecorded::class,  fn ($e) => $run(fn ($s) => $s->onPaymentRecorded($e->payment)));
        Event::listen(PaymentCancelled::class, fn ($e) => $run(fn ($s) => $s->onPaymentCancelled($e->payment)));

        // Journal de sécurité : jamais de mot de passe, uniquement l'e-mail saisi
        Event::listen(Login::class, fn ($e) => ActivityLogger::log(
            'login', 'Connexion de ' . $e->user->name, null, [], $e->user->id, $e->user->name
        ));

        Event::listen(Logout::class, function ($e) {
            if ($e->user) {
                ActivityLogger::log('logout', 'Déconnexion de ' . $e->user->name, null, [], $e->user->id, $e->user->name);
            }
        });

        Event::listen(Failed::class, fn ($e) => ActivityLogger::log(
            'login_failed', 'Échec de connexion : ' . ($e->credentials['email'] ?? '—'),
            null, [], null, $e->credentials['email'] ?? null
        ));

        Event::listen(Lockout::class, fn ($e) => ActivityLogger::log(
            'lockout', 'Trop de tentatives de connexion : ' . $e->request->input('email'),
            null, [], null, (string) $e->request->input('email')
        ));
    }
}
