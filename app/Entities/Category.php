<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Category extends Entity
{
    protected $dates = ['created_at', 'updated_at'];
    protected $casts = [
        'id'            => 'int',
        'sort_order'    => 'int',
        'product_count' => '?int',
    ];
}
