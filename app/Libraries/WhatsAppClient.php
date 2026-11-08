<?php

namespace App\Libraries;

use CodeIgniter\HTTP\Exceptions\HTTPException;
use Config\WhatsApp;

/** Sends a reply through the WhatsApp Cloud API. A failure is logged, never thrown. */
class WhatsAppClient
{
    /**
     * @param array<string, mixed> $reply a message fragment from WhatsAppBot
     */
    public function send(string $to, array $reply): void
    {
        $config = config(WhatsApp::class);

        try {
            $response = service('curlrequest')->post(
                "https://graph.facebook.com/{$config->graphVersion}/{$config->phoneNumberId}/messages",
                [
                    'headers'     => ['Authorization' => "Bearer {$config->accessToken}"],
                    'json'        => ['messaging_product' => 'whatsapp', 'recipient_type' => 'individual', 'to' => $to, ...$reply],
                    'http_errors' => false,
                    'timeout'     => 10,
                ],
            );
        } catch (HTTPException $e) {
            log_message('error', 'WhatsApp send failed: ' . $e->getMessage());

            return;
        }

        if ($response->getStatusCode() >= 300) {
            log_message('error', 'WhatsApp send failed with HTTP ' . $response->getStatusCode());
        }
    }
}
