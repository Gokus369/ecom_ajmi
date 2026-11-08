<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * The WhatsApp Cloud API connection, set in .env as whatsapp.phoneNumberId and so on.
 * The bot stays off until all four credentials are set.
 */
class WhatsApp extends BaseConfig
{
    public string $phoneNumberId = '';
    public string $accessToken   = '';
    public string $appSecret     = '';
    public string $verifyToken   = '';
    public string $graphVersion  = 'v26.0';

    public function isConnected(): bool
    {
        return $this->phoneNumberId !== ''
            && $this->accessToken !== ''
            && $this->appSecret !== ''
            && $this->verifyToken !== '';
    }
}
