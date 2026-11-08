<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Sign in with Google, set in .env as google.clientId and google.clientSecret.
 * The button stays hidden until both are set.
 */
class Google extends BaseConfig
{
    public string $clientId     = '';
    public string $clientSecret = '';

    public function isConnected(): bool
    {
        return $this->clientId !== '' && $this->clientSecret !== '';
    }
}
