<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Order extends Entity
{
    public const PENDING   = 'pending';
    public const CONFIRMED = 'confirmed';
    public const SHIPPED   = 'shipped';
    public const DELIVERED = 'delivered';
    public const CANCELLED = 'cancelled';

    public const STATUS_LABELS = [
        self::PENDING   => 'New',
        self::CONFIRMED => 'Confirmed',
        self::SHIPPED   => 'Shipped',
        self::DELIVERED => 'Delivered',
        self::CANCELLED => 'Cancelled',
    ];

    /** The steps an order can take from each status. Delivered and cancelled are final. */
    private const NEXT_STATUSES = [
        self::PENDING   => [self::CONFIRMED, self::CANCELLED],
        self::CONFIRMED => [self::SHIPPED, self::CANCELLED],
        self::SHIPPED   => [self::DELIVERED, self::CANCELLED],
        self::DELIVERED => [],
        self::CANCELLED => [],
    ];

    protected $dates = ['created_at', 'updated_at'];
    protected $casts = [
        'id'       => 'int',
        'subtotal' => 'int',
        'discount' => 'int',
        'shipping' => 'int',
        'total'    => 'int',
    ];

    /**
     * @return list<string>
     */
    public function nextStatuses(): array
    {
        return self::NEXT_STATUSES[$this->status] ?? [];
    }

    public function canMoveTo(string $status): bool
    {
        return in_array($status, $this->nextStatuses(), true);
    }

    /** Read as $order->status_label */
    public function getStatusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst((string) $this->status);
    }
}
