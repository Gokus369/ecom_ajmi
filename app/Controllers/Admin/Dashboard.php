<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Entities\Booking;
use App\Entities\Enquiry;
use App\Entities\Order;
use App\Models\BookingModel;
use App\Models\EnquiryModel;
use App\Models\OrderModel;
use App\Models\ProductModel;
use CodeIgniter\I18n\Time;
use Config\Store;

class Dashboard extends BaseController
{
    private const UPCOMING = 5;

    public function index(): string
    {
        $orders       = model(OrderModel::class);
        $products     = model(ProductModel::class);
        $bookings     = model(BookingModel::class);
        $counts       = $orders->countsByStatus();
        $newOrders    = (int) ($counts[Order::PENDING] ?? 0);
        $newBookings  = $bookings->where('status', Booking::PENDING)->countAllResults();
        $newEnquiries = model(EnquiryModel::class)->where('status', Enquiry::PENDING)->countAllResults();
        $store        = config(Store::class);

        return view('admin/dashboard', [
            'title'        => 'Dashboard',
            'attention'    => $this->attention($newOrders, $newBookings, $newEnquiries),
            'newOrders'    => $newOrders,
            'newBookings'  => $newBookings,
            'newEnquiries' => $newEnquiries,
            'toShip'       => (int) ($counts[Order::CONFIRMED] ?? 0),
            'revenue'      => $orders->revenueSince(Time::now()->subDays(30)->toDateTimeString()),
            'liveCount'    => $products->published()->countAllResults(),
            'recent'       => $orders->newest()->findAll(8),
            'upcoming'     => $bookings->upcoming(Time::now($store->timezone)->toDateString(), self::UPCOMING),
            'lowStock'     => $products->lowStock($store->lowStockAt),
        ]);
    }

    /** One sentence on what is waiting, such as "You have 2 new orders and 1 new booking." */
    private function attention(int $orders, int $bookings, int $enquiries): string
    {
        $waiting = array_values(array_filter([
            $this->count($orders, 'order', 'orders'),
            $this->count($bookings, 'booking', 'bookings'),
            $this->count($enquiries, 'enquiry', 'enquiries'),
        ]));

        if ($waiting === []) {
            return 'You are all caught up.';
        }

        $last = array_pop($waiting);

        return 'You have ' . ($waiting === [] ? $last : implode(', ', $waiting) . " and {$last}") . '.';
    }

    private function count(int $total, string $one, string $many): ?string
    {
        return $total > 0 ? number_format($total) . ' new ' . ($total === 1 ? $one : $many) : null;
    }
}
