<?php

return [
    'erp_base_url' => env('ERP_BASE_URL', 'http://localhost:8001'),
    'erp_username' => env('ERP_USERNAME'),
    'erp_password' => env('ERP_PASSWORD'),
    'erp_login_url' => env('ERP_LOGIN_URL', '/login'),
    'erp_cookie' => env('ERP_COOKIE'),
    'erp_xsrf_token' => env('ERP_XSRF_TOKEN'),
    'erp_timeout' => (int) env('ERP_TIMEOUT', 15),
];
