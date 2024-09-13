<?php

// config for ChrisReedIO/ScoutKeys
return [
    /**
     * Whether to register the Scout Indexing commands for use via web.
     */
    'register_commands' => false,

    'key' => [
        /**
         * The number of minutes a search key should be valid for.
         */
        'lifetime' => env('SCOUT_KEY_EXPIRATION', 60 * 12),
    ],
];
