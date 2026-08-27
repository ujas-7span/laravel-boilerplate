<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Developer Portal Switch
    |--------------------------------------------------------------------------
    |
    | Enable or disable access to the developer dashboard and internal tools.
    |
    */
    'enabled' => (bool) env('DEVELOPER_PORTAL_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Developer Credentials
    |--------------------------------------------------------------------------
    |
    | Credentials used to authenticate to the developer portal and tools.
    | Managed directly via environment variables without touching database tables.
    |
    */
    'username' => (string) env('DEVELOPER_USERNAME', 'developer'),
    'password' => (string) env('DEVELOPER_PASSWORD', 'developer123'),
];
