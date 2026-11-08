<?php

namespace App\Entities;

use App\Entities\Traits\Discount;
use CodeIgniter\Entity\Entity;

class Voucher extends Entity
{
    use Discount;

    protected $casts = [
        'id'             => 'int',
        'discount_value' => 'int',
        'min_order'      => 'int',
        'max_uses'       => 'int',
        'used_count'     => 'int',
        'is_active'      => 'boolean',
    ];

    public function isUsable(string $today): bool
    {
        return $this->is_active
            && $this->used_count < $this->max_uses
            && ($this->expires_on === null || $this->expires_on >= $today);
    }

    /**
     * Where the voucher stands on a date, as a label and its badge colour.
     *
     * @return array{0: string, 1: string}
     */
    public function state(string $today): array
    {
        return match (true) {
            ! $this->is_active                 => ['Off', 'status-cancelled'],
            $this->used_count >= $this->max_uses => ['Used', ''],
            ! $this->isUsable($today)          => ['Expired', ''],
            default                            => ['Ready', 'status-delivered'],
        };
    }
}
