<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class OrderItem extends Entity
{
    protected $casts = [
        'id'         => 'int',
        'order_id'   => 'int',
        'product_id' => '?int',
        'variant_id' => '?int',
        'unit_price' => 'int',
        'quantity'   => 'int',
        'line_total' => 'int',
    ];
}
