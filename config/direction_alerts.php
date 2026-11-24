<?php

return [
    // Désactivé par défaut : aucune notification ne part après installation.
    'enabled' => (bool) env('DIRECTION_EMAIL_ALERTS', false),
    'time' => env('DIRECTION_ALERT_TIME', '08:30'),
];
