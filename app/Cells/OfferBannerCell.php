<?php

namespace App\Cells;

use App\Entities\Offer;
use App\Models\OfferModel;
use CodeIgniter\View\Cells\Cell;

/**
 * The strip across the store while a festival offer is running.
 */
class OfferBannerCell extends Cell
{
    public ?Offer $offer = null;

    public function mount(): void
    {
        $this->offer = model(OfferModel::class)->running(store_today())[0] ?? null;
    }
}
