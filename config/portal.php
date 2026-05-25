<?php

return [
    'erp_base_url' => env('ERP_BASE_URL', 'http://localhost:8001'),
    'erp_b2b_products_path' => env('ERP_B2B_PRODUCTS_PATH', '/api/portal-b2b/products'),
    'erp_b2b_invoices_path' => env('ERP_B2B_INVOICES_PATH', '/api/portal-b2b/invoices'),
    'erp_b2b_account_path' => env('ERP_B2B_ACCOUNT_PATH', '/api/portal-b2b/account'),
    'erp_b2b_profile_path' => env('ERP_B2B_PROFILE_PATH', '/api/portal-b2b/profile'),
    'erp_b2b_dashboard_path' => env('ERP_B2B_DASHBOARD_PATH', '/api/portal-b2b/dashboard'),
    'erp_b2b_orders_path' => env('ERP_B2B_ORDERS_PATH', '/api/portal-b2b/orders'),
    'erp_b2b_quotes_path' => env('ERP_B2B_QUOTES_PATH', '/api/portal-b2b/quotes'),
    'erp_b2b_auth_identity_path' => env('ERP_B2B_AUTH_IDENTITY_PATH', '/api/portal-b2b/auth/identity'),
    'erp_b2b_client_id' => env('ERP_B2B_CLIENT_ID'),
    'erp_username' => env('ERP_USERNAME'),
    'erp_password' => env('ERP_PASSWORD'),
    'erp_login_url' => env('ERP_LOGIN_URL', '/login'),
    'erp_cookie' => env('ERP_COOKIE'),
    'erp_xsrf_token' => env('ERP_XSRF_TOKEN'),
    'erp_timeout' => (int) env('ERP_TIMEOUT', 15),
];
