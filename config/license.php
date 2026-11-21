<?php

return [
    'required' => env('LICENSE_REQUIRED', false),
    'issuer' => env('LICENSE_ISSUER'),
    'campaign_local_origin' => env('WEBFX_CAMPAIGN_LOCAL_ORIGIN'),
    'public_key' => env('LICENSE_PUBLIC_KEY'),
    'template' => env('WEBFX_TEMPLATE_SLUG'),
    'key' => env('LICENSE_KEY'),
];
