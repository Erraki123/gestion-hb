<?php

return [
    // ⚠️ يجب التأكد من النسبة والمعلومات القانونية مع المحاسب
    'tax_rate' => (float) env('TAX_RATE', 20),
    'currency' => 'DH',

    'company' => [
        'name'    => env('COMPANY_NAME', 'Ma Société'),
        'address' => env('COMPANY_ADDRESS', 'Casablanca, Maroc'),
        'phone'   => env('COMPANY_PHONE', ''),
        'email'   => env('COMPANY_EMAIL', ''),
        'ice'     => env('COMPANY_ICE', ''),
    ],

    'quote_validity_days' => 30,
    'invoice_footer' => env('INVOICE_FOOTER', 'ICE : ... · RC : ... · IF : ... · RIB : ...'),
    // ttc = المبلغ كما هو ؛ ht = تحويل المبلغ إلى ما قبل الضريبة
'commission_base' => env('COMMISSION_BASE', 'ttc'),
];
