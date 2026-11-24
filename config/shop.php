<?php

// Shop catalog: prices are defined on the server (in cents), never sent by the browser
return [
    'packs' => [
        'starter' => ['name' => 'Pack Starter', 'betcoins' => 500, 'price' => 199],
        'pro' => ['name' => 'Pack Pro', 'betcoins' => 1500, 'price' => 499],
        'legende' => ['name' => 'Pack Légende', 'betcoins' => 5000, 'price' => 999],
    ],

    // Responsible gaming: maximum number of purchases per 24 hours
    'daily_limit' => 3,
];
