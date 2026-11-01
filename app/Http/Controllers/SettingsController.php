<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Setting;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SettingsController extends Controller
{
    public function edit()
    {
        $v = [
            'company_name'        => Setting::get('company_name', config('commerce.company.name')),
            'company_address'     => Setting::get('company_address', config('commerce.company.address')),
            'company_phone'       => Setting::get('company_phone', config('commerce.company.phone')),
            'company_email'       => Setting::get('company_email', config('commerce.company.email')),
            'company_ice'         => Setting::get('company_ice', config('commerce.company.ice')),
            'invoice_footer'      => Setting::get('invoice_footer', config('commerce.invoice_footer')),
            'payment_days'        => Setting::get('payment_days', config('commerce.payment_days')),
            'quote_validity_days' => Setting::get('quote_validity_days', config('commerce.quote_validity_days')),
            'default_tax_rate'    => Setting::get('default_tax_rate', config('commerce.tax_rate')),
            'price_entry_mode'    => Setting::priceMode(),
            'commission_base'     => Setting::get('commission_base', config('commerce.commission_base')),
        ];

        $ratesText = implode(', ', array_map(fn ($r) => $r + 0, Setting::taxRates()));

        return view('settings.edit', compact('v', 'ratesText'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'company_name'        => ['required', 'string', 'max:120'],
            'company_address'     => ['nullable', 'string', 'max:255'],
            'company_phone'       => ['nullable', 'string', 'max:50'],
            'company_email'       => ['nullable', 'email', 'max:120'],
            'company_ice'         => ['nullable', 'string', 'max:30'],
            'invoice_footer'      => ['nullable', 'string', 'max:1000'],
            'payment_days'        => ['required', 'integer', 'min:0', 'max:365'],
            'quote_validity_days' => ['required', 'integer', 'min:1', 'max:365'],
            'tax_rates'           => ['required', 'string', 'max:100'],
            'default_tax_rate'    => ['required', 'numeric', 'min:0', 'max:100'],
            'price_entry_mode'    => ['required', 'in:ht,ttc'],
            'commission_base'     => ['required', 'in:ttc,ht'],
        ]);

        $rates = $this->parseRates($data['tax_rates']);

        if (! in_array((float) $data['default_tax_rate'], $rates, true)) {
            throw ValidationException::withMessages(['default_tax_rate' => 'Le taux par défaut doit faire partie de la liste des taux.']);
        }

        if (! in_array((float) $data['default_tax_rate'], $rates, true)) {
            throw ValidationException::withMessages(['default_tax_rate' => 'Le taux par défaut doit faire partie de la liste des taux.']);
        }

        $used = Product::withTrashed()->whereNotIn('tax_rate', $rates)->distinct()->pluck('tax_rate')
            ->map(fn ($r) => (float) $r + 0)->all();

        if ($used) {
            throw ValidationException::withMessages([
                'tax_rates' => 'Des produits utilisent encore le(s) taux : ' . implode(' %, ', $used) . ' %. Changez d\'abord leur taux.',
            ]);
        }

        $payload = [
            'company_name'        => $data['company_name'],
            'company_address'     => $data['company_address'] ?? null,
            'company_phone'       => $data['company_phone'] ?? null,
            'company_email'       => $data['company_email'] ?? null,
            'company_ice'         => $data['company_ice'] ?? null,
            'invoice_footer'      => $data['invoice_footer'] ?? null,
            'payment_days'        => $data['payment_days'],
            'quote_validity_days' => $data['quote_validity_days'],
            'tax_rates'           => json_encode($rates),
            'default_tax_rate'    => (float) $data['default_tax_rate'],
            'price_entry_mode'    => $data['price_entry_mode'],
            'commission_base'     => $data['commission_base'],
        ];

        $before = [];
        $after = [];
        foreach ($payload as $k => $val) {
            $old = Setting::get($k);
            if ((string) $old !== (string) $val) {
                $before[$k] = $old;
                $after[$k] = $val;
            }
        }

        Setting::put($payload);

        if ($after) {
            ActivityLogger::log('updated', 'Modification des paramètres (' . implode(', ', array_keys($after)) . ')', null,
                ['before' => $before, 'after' => $after]);
        }

        return back()->with('success', 'Paramètres enregistrés.');
    }

    private function parseRates(string $text): array
    {
        $parts = preg_split('/[\s,;]+/', trim($text), -1, PREG_SPLIT_NO_EMPTY);
        $rates = [];

        foreach ($parts as $p) {
            $p = str_replace(',', '.', $p);

            if (! is_numeric($p) || (float) $p < 0 || (float) $p > 100) {
                throw ValidationException::withMessages(['tax_rates' => 'Chaque taux doit être un nombre entre 0 et 100.']);
            }

            $rates[] = round((float) $p, 2);
        }

        $rates = array_values(array_unique($rates));
        rsort($rates);

        if (! $rates || count($rates) > 10) {
            throw ValidationException::withMessages(['tax_rates' => 'Indiquez entre 1 et 10 taux.']);
        }

        return $rates;
    }
}
