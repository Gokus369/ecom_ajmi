<?php

namespace App\Controllers;

use App\Libraries\Assistant;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;

/** The website chat. Nothing is stored: each message is answered and forgotten. */
class Chat extends BaseController
{
    public function reply(): ResponseInterface
    {
        // Handovers need somewhere to go, so the chat is off without a WhatsApp number.
        if (whatsapp_url() === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $message = $this->request->getPost('message');
        $answer  = (new Assistant())->answer(is_string($message) ? mb_substr($message, 0, 500) : '');
        $text    = $answer['text'];
        $links   = $answer['links'];

        if ($answer['team'] !== null) {
            $text .= ' Continue on WhatsApp and someone from the team will reply.';
            $links[] = ['label' => 'Continue on WhatsApp', 'url' => whatsapp_url($answer['team'])];
        }

        return $this->response->setJSON(['text' => $text, 'links' => $links, 'options' => $answer['options']]);
    }
}
