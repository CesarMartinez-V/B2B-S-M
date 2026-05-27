<?php

return [
    'fastevo' => [
        'base_url' => 'http://127.0.0.1:8001',
        'timeout' => 15,
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
