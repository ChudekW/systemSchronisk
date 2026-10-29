<?php

use App\Models\Animal;

return [
    'driver' => env('SCOUT_DRIVER', 'meilisearch'),
    'prefix' => env('SCOUT_PREFIX', 'laparazem_'),
    'queue' => env('SCOUT_QUEUE', true) ? ['queue' => 'scout'] : false,
    'after_commit' => true,
    'chunk' => ['searchable' => 500, 'unsearchable' => 500],
    'soft_delete' => false,
    'identify' => false,
    'meilisearch' => [
        'host' => env('MEILISEARCH_HOST', 'http://meilisearch:7700'),
        'key' => env('MEILISEARCH_KEY'),
        'index-settings' => [
            Animal::class => [
                'searchableAttributes' => ['name', 'registration_number', 'microchip', 'breed', 'color', 'location'],
                'filterableAttributes' => ['organization_id', 'species', 'status'],
                'sortableAttributes' => ['admitted_at', 'id'],
                'displayedAttributes' => ['id'],
                'typoTolerance' => ['disableOnAttributes' => ['registration_number', 'microchip']],
                'pagination' => ['maxTotalHits' => 10000],
            ],
        ],
    ],
];
