<?php
return [
    // OFF until the organization explicitly enables a source and approves its terms.
    'enabled' => env('PROSPECT_DISCOVERY_ENABLED', false),
    'osm_enabled' => env('PROSPECT_DISCOVERY_OSM_ENABLED', false),
    'overpass_url' => 'https://overpass-api.de/api/interpreter',
    'user_agent' => env('PROSPECT_DISCOVERY_USER_AGENT', 'HNBloom-CRM/1.0 (contact: admin@example.org)'),
    'max_candidates_per_run' => 60,
    // Other licensed business-data sources must expose the documented JSON format.
    // Only URLs deliberately entered by the administrator here; NEVER URLs from users/AI.
    'approved_feeds' => [],
];
