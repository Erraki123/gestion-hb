<?php

/**
 * Envoi automatique (e-mail + WhatsApp) et alarme des chèques.
 * Tout se règle depuis le fichier .env : rien ne part tant que les interrupteurs sont à false.
 */
return [
    // true = envoi en arrière-plan (queue). false = envoi immédiat.
    'queue' => (bool) env('NOTIFY_QUEUE', true),

    'email' => [
        'enabled' => (bool) env('NOTIFY_EMAIL', false),
    ],

    'whatsapp' => [
        'enabled'      => (bool) env('NOTIFY_WHATSAPP', false),
        'base_url'     => env('WABRIDGE_URL', 'https://wabridges.com'),
        'instance'     => env('WABRIDGE_INSTANCE'),
        'key'          => env('WABRIDGE_KEY'),
        'country_code' => env('WABRIDGE_COUNTRY_CODE', '212'),
        // petit délai aléatoire (secondes) avant chaque envoi, pour ne pas ressembler à un robot
        'delay_min'    => (int) env('WABRIDGE_DELAY_MIN', 5),
        'delay_max'    => (int) env('WABRIDGE_DELAY_MAX', 25),
    ],

    // Envoi automatique au client quand le document est créé / émis
    'auto' => [
        'quote'   => (bool) env('NOTIFY_AUTO_QUOTE', false),
        'invoice' => (bool) env('NOTIFY_AUTO_INVOICE', false),
    ],

    // Lien PDF envoyé par WhatsApp : durée de validité (jours)
    'link_days' => (int) env('NOTIFY_LINK_DAYS', 30),

    'digest' => [
        'enabled' => (bool) env('CHEQUE_DIGEST', true),
        'days'    => (int) env('CHEQUE_DIGEST_DAYS', 3),
    ],
];
