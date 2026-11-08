<?php

namespace App\Entities;

use App\Entities\Traits\Discount;
use CodeIgniter\Entity\Entity;

class Offer extends Entity
{
    use Discount;

    public const PERCENT = 'percent';
    public const AMOUNT  = 'amount';

    public const DISCOUNT_TYPES = [
        self::PERCENT => 'Percent off',
        self::AMOUNT  => 'Amount off (₹)',
    ];

    protected $casts = [
        'id'             => 'int',
        'discount_value' => 'int',
        'min_order'      => 'int',
        'is_active'      => 'boolean',
    ];

    /**
     * Where the offer stands on a date, as a label and its badge colour.
     *
     * @return array{0: string, 1: string}
     */
    public function state(string $today): array
    {
        return match (true) {
            ! $this->is_active       => ['Off', 'status-cancelled'],
            $today < $this->starts_on => ['Scheduled', 'status-pending'],
            $today > $this->ends_on   => ['Ended', ''],
            default                  => ['Running', 'status-delivered'],
        };
    }
}
