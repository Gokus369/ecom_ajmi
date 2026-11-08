<?php

namespace App\Libraries;

/**
 * Presents the assistant's answers on WhatsApp. Replies are Graph API message
 * fragments, or null while the team has the conversation.
 */
class WhatsAppBot
{
    /**
     * @return array<string, mixed>|null
     */
    public function reply(string $sender, string $input): ?array
    {
        $pause = 'whatsapp_paused_' . sha1($sender);

        if (preg_match('/^\s*menu\s*$/i', $input) === 1) {
            cache()->delete($pause);
        } elseif (cache($pause) !== null) {
            return null;
        }

        $answer = (new Assistant())->answer($input);

        if ($answer['options'] !== []) {
            return $this->menu($answer['text'], $answer['options']);
        }

        if ($answer['team'] !== null) {
            cache()->save($pause, true, DAY);
            $answer['text'] .= ' Someone from the team will reply here.';
        }

        return $this->text($answer['text'], $answer['links']);
    }

    /**
     * @param list<array{label: string, url: string}> $links
     *
     * @return array<string, mixed>
     */
    private function text(string $text, array $links): array
    {
        foreach ($links as $link) {
            $text .= "\n" . (str_starts_with($link['url'], 'tel:') ? $link['label'] : "{$link['label']}: {$link['url']}");
        }

        return [
            'type' => 'text',
            'text' => ['preview_url' => false, 'body' => "{$text}\n\nSend *menu* to see the options again."],
        ];
    }

    /**
     * @param list<array{id: string, title: string, description: string}> $rows
     *
     * @return array<string, mixed>
     */
    private function menu(string $text, array $rows): array
    {
        return [
            'type'        => 'interactive',
            'interactive' => [
                'type'   => 'list',
                'body'   => ['text' => $text],
                'action' => [
                    'button'   => 'See options',
                    'sections' => [['title' => 'How can we help?', 'rows' => $rows]],
                ],
            ],
        ];
    }
}
