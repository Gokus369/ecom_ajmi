<?php

namespace App\Models;

use App\Entities\Offer;
use CodeIgniter\Model;

class OfferModel extends Model
{
    protected $table         = 'offers';
    protected $primaryKey    = 'id';
    protected $returnType    = Offer::class;
    protected $useTimestamps = true;
    protected $allowedFields = ['name', 'discount_type', 'discount_value', 'min_order', 'starts_on', 'ends_on', 'is_active'];

    /**
     * @return list<Offer> offers live on a date, the soonest to end first
     */
    public function running(string $today): array
    {
        return $this->where('is_active', 1)
            ->where('starts_on <=', $today)
            ->where('ends_on >=', $today)
            ->orderBy('ends_on', 'ASC')
            ->findAll();
    }

    /** The running offer that takes the most off this subtotal, if any does. */
    public function best(int $subtotal, string $today): ?Offer
    {
        $best = null;

        foreach ($this->running($today) as $offer) {
            if ($offer->amountOff($subtotal) > ($best?->amountOff($subtotal) ?? 0)) {
                $best = $offer;
            }
        }

        return $best;
    }
}
