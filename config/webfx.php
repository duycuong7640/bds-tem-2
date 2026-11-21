<?php

return [
    'local_admin_email' => env('LOCAL_ADMIN_EMAIL', 'admin@gmail.com'),
    'local_admin_password' => env('LOCAL_ADMIN_PASSWORD', '123123!@A'),
    'runtime' => env('WEBFX_RUNTIME', false),
    'sample' => env('WEBFX_TEMPLATE_SAMPLE', 'real-estate'),
    'demo' => (string) env('WEBFX_DEMO', '0') === '1',
];
