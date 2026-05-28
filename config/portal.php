<?php

return [
    'fastevo' => [
        'base_url' => 'https://dev.capgrupo.com',
        'timeout' => 15,
        'verify_ssl' => env('PORTAL_FASTEVO_VERIFY_SSL', env('APP_ENV', 'production') !== 'local'),
        'paths' => [
            'auth_identity' => '/api/portal-b2b/auth/identity',
            'auth_check_identity' => '/api/portal-b2b/auth/check-identity',
            'auth_create_password' => '/api/portal-b2b/auth/create-password',
            'products' => '/api/portal-b2b/products',
            'invoices' => '/api/portal-b2b/invoices',
            'account' => '/api/portal-b2b/account',
            'profile' => '/api/portal-b2b/profile',
            'dashboard' => '/api/portal-b2b/dashboard',
            'orders' => '/api/portal-b2b/orders',
            'quotes' => '/api/portal-b2b/quotes',
        ],
    ],
];
