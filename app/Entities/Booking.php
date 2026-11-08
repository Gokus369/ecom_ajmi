<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Booking extends Entity
{
    public const PENDING   = 'pending';
    public const CONFIRMED = 'confirmed';
    public const COMPLETED = 'completed';
    public const CANCELLED = 'cancelled';

    public const STATUS_LABELS = [
        self::PENDING   => 'New',
        self::CONFIRMED => 'Confirmed',
        self::COMPLETED => 'Completed',
        self::CANCELLED => 'Cancelled',
    ];

    /** The steps a booking can take from each status. Completed and cancelled are final. */
    private const NEXT_STATUSES = [
        self::PENDING   => [self::CONFIRMED, self::CANCELLED],
        self::CONFIRMED => [self::COMPLETED, self::CANCELLED],
        self::COMPLETED => [],
        self::CANCELLED => [],
    ];

    protected $dates = ['created_at', 'updated_at'];
    protected $casts = [
        'id'     => 'int',
        'people' => 'int',
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

    /** Read as $booking->status_label */
    public function getStatusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst((string) $this->status);
    }

    /** Read as $booking->date_label */
    public function getDateLabel(): string
    {
        return date('j M Y', strtotime($this->attributes['wedding_date']));
    }

    /** Read as $booking->ready_by_label */
    public function getReadyByLabel(): ?string
    {
        return $this->attributes['ready_by'] ? date('g:i a', strtotime($this->attributes['ready_by'])) : null;
    }
}
