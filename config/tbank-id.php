<?php

return [
    'client_id' => env('TBANK_ID_CLIENT_ID', ''),
    
    'client_secret' => env('TBANK_ID_CLIENT_SECRET', ''),
    
    'redirect_uri' => env('TBANK_ID_REDIRECT_URI', ''),
    
    'scopes' => [
        'openid',
        'email',
        'phone',
    ],
];
