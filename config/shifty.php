<?php

return [
    'installed' => false,
    'organization' => null,
    'default_shifts_expected' => env('DEFAULT_SHIFTS_EXPECTED', 0),
    'self_registration' => true,
    'timezone' => 'UTC',
    'event_start' => null,
    'event_end' => null,
    'accent_color' => null,
    'assets' => [],
    'password_login' => true,
    'sso' => [
        'enabled' => env('IDENTITY_CLIENT_ID') !== null,
        'label' => 'Single sign-on',
        'scopes' => 'openid profile email',
        'access_claim' => null,
        'access_value' => null,
        'admin_claim' => null,
        'admin_value' => null,
        'auto_create' => true,
        'link_by_email' => false,
    ],
];
