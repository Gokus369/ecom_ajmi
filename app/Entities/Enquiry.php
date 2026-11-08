<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Enquiry extends Entity
{
    public const CUSTOM = 'custom';
    public const BRIDAL = 'bridal';

    public const TYPE_LABELS = [
        self::CUSTOM => 'Custom styling',
        self::BRIDAL => 'Bridal consultation',
    ];

    public const WEBSITE   = 'website';
    public const INSTAGRAM = 'instagram';
    public const WHATSAPP  = 'whatsapp';
    public const REFERRAL  = 'referral';
    public const WALK_IN   = 'walk_in';
    public const OTHER     = 'other';

    /** Where a customer came from. Enquiries from the site have no source saved. */
    public const SOURCE_LABELS = [
        self::INSTAGRAM => 'Instagram',
        self::WHATSAPP  => 'WhatsApp',
        self::REFERRAL  => 'Referred by someone',
        self::WALK_IN   => 'Walk-in',
        self::WEBSITE   => 'Website',
        self::OTHER     => 'Other',
    ];

    public const PENDING   = 'pending';
    public const CONTACTED = 'contacted';
    public const CLOSED    = 'closed';

    public const STATUS_LABELS = [
        self::PENDING   => 'New',
        self::CONTACTED => 'Contacted',
        self::CLOSED    => 'Closed',
    ];

    /** The steps an enquiry can take from each status. Closed is final. */
    private const NEXT_STATUSES = [
        self::PENDING   => [self::CONTACTED, self::CLOSED],
        self::CONTACTED => [self::CLOSED],
        self::CLOSED    => [],
    ];

    protected $dates = ['created_at', 'updated_at'];
    protected $casts = ['id' => 'int', 'order_value' => '?int'];

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

    /** Read as $enquiry->status_label */
    public function getStatusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst((string) $this->status);
    }

    /** Read as $enquiry->type_label */
    public function getTypeLabel(): string
    {
        return self::TYPE_LABELS[$this->type] ?? ucfirst((string) $this->type);
    }

    /** Read as $enquiry->source_label */
    public function getSourceLabel(): string
    {
        return self::SOURCE_LABELS[$this->attributes['source'] ?? self::WEBSITE] ?? 'Website';
    }

    /** Read as $enquiry->date_label, null when the customer gave no date. */
    public function getDateLabel(): ?string
    {
        $date = $this->attributes['event_date'] ?? null;

        return $date ? date('j M Y', strtotime($date)) : null;
    }
}
