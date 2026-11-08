<?php

namespace App\Controllers;

use App\Libraries\WhatsAppBot;
use CodeIgniter\HTTP\ResponseInterface;
use Config\WhatsApp;

class WhatsAppWebhook extends BaseController
{
    /** Meta calls this once to confirm the callback URL belongs to us. */
    public function verify(): ResponseInterface
    {
        $config    = config(WhatsApp::class);
        $token     = $this->request->getGet('hub_verify_token');
        $challenge = $this->request->getGet('hub_challenge');

        if (
            ! $config->isConnected()
            || $this->request->getGet('hub_mode') !== 'subscribe'
            || ! is_string($token)
            || ! is_string($challenge)
            || ! hash_equals($config->verifyToken, $token)
        ) {
            return $this->response->setStatusCode(403);
        }

        return $this->response->setContentType('text/plain')->setBody($challenge);
    }

    public function receive(): ResponseInterface
    {
        $config   = config(WhatsApp::class);
        $body     = (string) $this->request->getBody();
        $expected = 'sha256=' . hash_hmac('sha256', $body, $config->appSecret);

        // Checked first because an empty secret would let anyone sign a request.
        if (! $config->isConnected() || ! hash_equals($expected, $this->request->getHeaderLine('X-Hub-Signature-256'))) {
            return $this->response->setStatusCode(403);
        }

        $payload = json_decode($body, true);

        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                foreach ($change['value']['messages'] ?? [] as $message) {
                    $this->answer($message);
                }
            }
        }

        // Always 200: Meta redelivers otherwise, and a failed send is already logged.
        return $this->response->setStatusCode(200);
    }

    /**
     * @param array<string, mixed> $message
     */
    private function answer(array $message): void
    {
        $seen = 'whatsapp_seen_' . sha1((string) ($message['id'] ?? ''));

        if (cache($seen) !== null) {
            return;
        }

        cache()->save($seen, true, DAY);

        $input = $message['text']['body'] ?? $message['interactive']['list_reply']['id'] ?? '';
        $reply = (new WhatsAppBot())->reply($message['from'], $input);

        if ($reply !== null) {
            service('whatsapp')->send($message['from'], $reply);
        }
    }
}
