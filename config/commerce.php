<?php
return [
    // Choix du modèle : signature, prestige ou editorial
    'pdf_template' => env('HN_BLOOM_PDF_TEMPLATE', 'signature'),
    'company' => [
        'name' => env('HN_BLOOM_NAME', 'HN BLOOM'),
        'address' => env('HN_BLOOM_ADDRESS', '120, bd La grand ceinture IMM Liliya II, ETG 3 N°27, 20570 Casablanca'),
        'phone' => env('HN_BLOOM_PHONE', '0522351333'),
        'email' => env('HN_BLOOM_EMAIL', 'contact@hnbloom.ma'),
        'website' => env('HN_BLOOM_WEBSITE', 'www.hnbloom.ma'),
        'rc' => env('HN_BLOOM_RC', '597891'),
        'patente' => env('HN_BLOOM_PATENTE', '35554409'),
        'if' => env('HN_BLOOM_IF', '53992490'),
        'cnss' => env('HN_BLOOM_CNSS', '5918706'),
        'ice' => env('HN_BLOOM_ICE', '003367936000027'),
    ],
];
