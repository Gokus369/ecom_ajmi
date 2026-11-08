<?php

namespace App\Libraries;

use App\Entities\Order;
use App\Models\OrderModel;
use Config\Store;

/**
 * Works out the answer to a customer's message. The website chat and the WhatsApp
 * bot each present it in their own way, using only details from the store settings.
 */
class Assistant
{
    private const ORDER_NUMBER = '[2-9A-HJKMNP-Z]{10}';

    /** Menu ids are matched here too. Order matters: a product link can mention any word. */
    private const INTENTS = [
        'piece'   => '/\binterested in\b/i',
        'draping' => '/\bdrap\w*/i',
        'bridal'  => '/\b(bridal|bride|wedding)\b/i',
        'custom'  => '/\b(bespoke|custom\w*)\b/i',
        'order'   => '/\b(order|track\w*)\b/i',
        'shop'    => '/\b(shop|collection|catalogue)\b/i',
        'visit'   => '/\b(visit|boutique|address|location|directions?|where)\b/i',
        'team'    => '/\b(team|person|human|agent|call)\b/i',
    ];

    /**
     * team holds a ready-made message for a person when one should take over.
     * options lists the menu, and is only set when the menu is the answer.
     *
     * @return array{
     *     text: string,
     *     links: list<array{label: string, url: string}>,
     *     team: string|null,
     *     options: list<array{id: string, title: string, description: string}>
     * }
     */
    public function answer(string $input): array
    {
        $order = $this->orderIn($input);

        if ($order !== null) {
            return $this->say(
                "Order {$order->number}\nStatus: {$order->status_label}",
                [['label' => 'View order', 'url' => url_to('orders.show', $order->number)]],
            );
        }

        if (preg_match('/^\s*' . self::ORDER_NUMBER . '\s*$/i', $input) === 1) {
            return $this->say('We could not find that order number. Please check it against your order page.');
        }

        $hello = 'Hi ' . config(Store::class)->shortName . ', ';

        return match ($this->intent($input)) {
            'piece'  => $this->say('Thanks for asking about this piece.', team: trim($input)),
            'draping' => $this->say('Book saree draping for your wedding here, and we will contact you to confirm.', [['label' => 'Book saree draping', 'url' => url_to('draping')]]),
            'bridal' => $this->say('Tell us your wedding date and the look you have in mind.', team: "{$hello}I'd like to book a bridal consultation."),
            'custom' => $this->say('Tell us the occasion, your date and any colours or references you love.', team: "{$hello}I'd like a custom piece."),
            'team'   => $this->say('Thanks.', team: "{$hello}I'd like to talk to the team."),
            'order'  => $this->say('Send your order number and we will check it. You will find it on your order page, and it looks like 72FMHC2ZBQ.'),
            'shop'   => $this->say('Here is the full collection.', [['label' => 'Shop the collection', 'url' => url_to('shop')]]),
            'visit'  => $this->visit() ?? $this->menu(),
            default  => $this->menu(),
        };
    }

    private function intent(string $input): ?string
    {
        foreach (self::INTENTS as $intent => $pattern) {
            if (preg_match($pattern, $input) === 1) {
                return $intent;
            }
        }

        return null;
    }

    private function orderIn(string $input): ?Order
    {
        preg_match_all('/\b' . self::ORDER_NUMBER . '\b/', strtoupper($input), $matches);

        return $matches[0] === [] ? null : model(OrderModel::class)->whereIn('number', $matches[0])->first();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function visit(): ?array
    {
        $store = config(Store::class);
        $maps  = maps_url();

        if ($maps === null) {
            return null;
        }

        $links = [['label' => 'Directions', 'url' => $maps]];

        if ($store->phone !== '') {
            $links[] = ['label' => "Call {$store->phone}", 'url' => phone_url()];
        }

        return $this->say("{$store->name}\n{$store->address}", $links);
    }

    /**
     * @return array<string, mixed>
     */
    private function menu(): array
    {
        $store = config(Store::class);

        $options = [
            ['id' => 'custom', 'title' => 'Custom piece', 'description' => 'A style made for you'],
            ['id' => 'bridal', 'title' => 'Bridal consultation', 'description' => 'Talk through your wedding look'],
            ['id' => 'draping', 'title' => 'Saree draping', 'description' => 'Book draping for your wedding'],
            ['id' => 'shop', 'title' => 'Shop the collection', 'description' => 'Browse pieces on our website'],
            ['id' => 'order', 'title' => 'Track my order', 'description' => 'Check your order status'],
        ];

        if ($store->address !== '') {
            $options[] = ['id' => 'visit', 'title' => 'Visit the boutique', 'description' => 'Address and directions'];
        }

        $options[] = ['id' => 'team', 'title' => 'Talk to the team', 'description' => 'Message a person directly'];

        return $this->say("Hello from {$store->name}. I'm the automated assistant. How can I help?", options: $options);
    }

    /**
     * @param list<array{label: string, url: string}>                          $links
     * @param list<array{id: string, title: string, description: string}>      $options
     *
     * @return array<string, mixed>
     */
    private function say(string $text, array $links = [], ?string $team = null, array $options = []): array
    {
        return ['text' => $text, 'links' => $links, 'team' => $team, 'options' => $options];
    }
}
