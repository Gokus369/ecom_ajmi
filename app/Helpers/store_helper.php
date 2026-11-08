<?php

use CodeIgniter\I18n\Time;
use Config\Store;

if (! function_exists('line_options')) {
    /**
     * The colour and size chosen for a cart line or order item, to show under its name.
     */
    function line_options(?string $colour, ?string $size): string
    {
        $parts = [];

        if (($colour ?? '') !== '') {
            $parts[] = "Colour: {$colour}";
        }

        if (($size ?? '') !== '') {
            $parts[] = "Size: {$size}";
        }

        return implode(' · ', $parts);
    }
}

if (! function_exists('store_today')) {
    /**
     * Today in the store's timezone, as offers and vouchers are dated in it.
     */
    function store_today(): string
    {
        return Time::now(config(Store::class)->timezone)->toDateString();
    }
}

if (! function_exists('money')) {
    /**
     * Formats an amount in minor units (paise) for display: 34900 becomes ₹349.
     */
    function money(int $minor): string
    {
        $decimals = $minor % 100 === 0 ? 0 : 2;

        return config(Store::class)->currency . number_format($minor / 100, $decimals);
    }
}

if (! function_exists('money_input')) {
    /**
     * Formats minor units for an admin form field: 34900 becomes 349.00
     */
    function money_input(int $minor): string
    {
        return number_format($minor / 100, 2, '.', '');
    }
}

if (! function_exists('to_minor')) {
    /**
     * Converts a validated decimal string to minor units: "349.5" becomes 34950.
     */
    function to_minor(string $amount): int
    {
        return (int) round((float) $amount * 100);
    }
}

if (! function_exists('local_time')) {
    /**
     * Shows a stored UTC time in the store's timezone.
     */
    function local_time(?Time $time, string $format = 'j M Y, g:i a'): string
    {
        if ($time === null) {
            return '';
        }

        return $time->setTimezone(config(Store::class)->timezone)->format($format);
    }
}

if (! function_exists('whatsapp_url')) {
    /**
     * A wa.me link to the store's number with a message ready to send,
     * or null when no number is set.
     */
    function whatsapp_url(string $message = ''): ?string
    {
        $number = preg_replace('/\D/', '', config(Store::class)->whatsapp);

        if ($number === '') {
            return null;
        }

        return 'https://wa.me/' . $number . ($message === '' ? '' : '?text=' . rawurlencode($message));
    }
}

if (! function_exists('instagram_url')) {
    function instagram_url(): ?string
    {
        $handle = config(Store::class)->instagram;

        return $handle === '' ? null : 'https://www.instagram.com/' . rawurlencode($handle) . '/';
    }
}

if (! function_exists('phone_url')) {
    function phone_url(): ?string
    {
        $number = preg_replace('/[^0-9+]/', '', config(Store::class)->phone);

        return $number === '' ? null : 'tel:' . $number;
    }
}

if (! function_exists('maps_url')) {
    /**
     * A Google Maps search for the boutique, or null when no address is set.
     */
    function maps_url(): ?string
    {
        $store = config(Store::class);

        if ($store->address === '') {
            return null;
        }

        return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode("{$store->name}, {$store->address}");
    }
}

if (! function_exists('collection_image_url')) {
    /**
     * The cover in public/assets/collections named after a category, if there is one.
     */
    function collection_image_url(string $slug): ?string
    {
        $path = 'collections/' . basename($slug) . '.webp';

        return is_file(FCPATH . 'assets/' . $path) ? asset_url($path) : null;
    }
}

if (! function_exists('partial')) {
    /**
     * Renders app/Views/partials/{name}. Unlike view(), the data does not carry
     * over to later views, so a field's $hint can't appear on the next field and
     * a loop of product cards can't overwrite the page's $product.
     *
     * @param array<string, mixed> $data
     */
    function partial(string $name, array $data = []): string
    {
        return view('partials/' . $name, $data, ['saveData' => false]);
    }
}

if (! function_exists('asset_url')) {
    /**
     * URL for a file in public/assets, versioned so a deploy busts browser caches.
     */
    function asset_url(string $path): string
    {
        $path = ltrim($path, '/');
        $file = FCPATH . 'assets/' . $path;

        return base_url('assets/' . $path) . '?v=' . (is_file($file) ? filemtime($file) : 0);
    }
}
