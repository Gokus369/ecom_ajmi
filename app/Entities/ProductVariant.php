<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

/**
 * One colour and size combination of a piece, with its own stock. A piece with only
 * colours or only sizes leaves the other empty. Read through ProductVariantModel,
 * which adds the names as colour, colour_hex and size.
 */
class ProductVariant extends Entity
{
    protected $casts = [
        'id'         => 'int',
        'product_id' => 'int',
        'colour_id'  => '?int',
        'size_id'    => '?int',
        'stock'      => 'int',
    ];

    public function matches(string $colour, string $size): bool
    {
        return (string) $this->colour === $colour && (string) $this->size === $size;
    }
}
