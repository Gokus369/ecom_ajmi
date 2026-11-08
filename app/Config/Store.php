<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Store-wide settings. Override any of these in .env, e.g. store.shippingFee = 60
 */
class Store extends BaseConfig
{
    public string $name      = 'RUA by Ajmi Anzari';
    public string $shortName = 'RUA';
    public string $byline    = 'by Ajmi Anzari';
    public string $tagline   = 'Custom styles that speak your soul';

    /** Shown to search engines and in the footer. */
    public string $description = 'Custom ethnic wear, bespoke bridal outfits and designer couture by Ajmi Anzari, from Kochi, Kerala.';

    public string $currency = '₹';

    /** Times are stored in UTC and shown to people in this zone. */
    public string $timezone = 'Asia/Kolkata';

    /** Flat delivery fee per order, in whole currency units. 0 makes delivery free. */
    public int $shippingFee = 50;

    /** Orders at or above this amount ship free, in whole currency units. 0 turns this off. */
    public int $freeShippingOver = 999;

    /** Products at or below this stock level show as "low stock" in the admin. */
    public int $lowStockAt = 2;

    /** Business number with country code. Empty hides every WhatsApp button. */
    public string $whatsapp = '';

    /** Instagram handle without the @. Empty hides every Instagram link. */
    public string $instagram = '';

    public string $contactEmail = '';

    /** Boutique phone with country code. Empty hides every Call button. */
    public string $phone = '';

    /** Boutique address on one line. Empty hides the Visit section and directions. */
    public string $address = '';

    /**
     * respect follows each visitor's "reduce motion" device setting.
     * always plays the animations for everyone, whatever that setting says.
     */
    public string $motion = 'respect';
}
