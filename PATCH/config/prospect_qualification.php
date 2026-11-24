<?php
return [
    // IA calls happen only if BOTH settings and DEEPSEEK_API_KEY exist.
    'ai_enabled' => env('PROSPECT_QUALIFICATION_AI_ENABLED', false),
    'daily_limit' => 8,
    'max_name_length' => 180,
];
