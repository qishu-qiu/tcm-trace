<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Security extends BaseConfig
{
    public string $csrfProtection = 'cookie';

    public bool $tokenRandomize = false;

    public string $tokenName = 'csrf_token';

    public string $headerName = 'X-CSRF-TOKEN';

    public string $cookieName = 'csrf_cookie';

    public int $expires = 7200;

    public bool $regenerate = true;

    public bool $redirect = false;

    public array $excludeURIs = [
        'api/scan/*',
        'api/auth/*',
    ];
}
