<?php

namespace App\Cells;

use App\Entities\Booking;
use App\Entities\Enquiry;
use App\Entities\Order;
use App\Models\BookingModel;
use App\Models\EnquiryModel;
use App\Models\OrderModel;
use CodeIgniter\View\Cells\Cell;

/**
 * The admin menu, with a count beside anything new. Nothing else alerts the
 * team, so this shows on every admin page.
 */
class AdminNavCell extends Cell
{
    /** @var list<array{group: string, key: string, route: string, label: string, icon: string, new: int}> */
    public array $links = [];

    public string $section = '';

    public function mount(): void
    {
        $this->section = explode('/', uri_string())[1] ?? '';

        $this->links = [
            ['group' => 'Overview', 'key' => '', 'route' => 'admin.home', 'label' => 'Dashboard', 'icon' => 'dashboard', 'new' => 0],
            ['group' => 'Overview', 'key' => 'analytics', 'route' => 'admin.analytics', 'label' => 'Analytics', 'icon' => 'chart', 'new' => 0],
            ['group' => 'Sales', 'key' => 'orders', 'route' => 'admin.orders', 'label' => 'Orders', 'icon' => 'orders', 'new' => model(OrderModel::class)->where('status', Order::PENDING)->countAllResults()],
            ['group' => 'Sales', 'key' => 'enquiries', 'route' => 'admin.enquiries', 'label' => 'Enquiries', 'icon' => 'enquiries', 'new' => model(EnquiryModel::class)->where('status', Enquiry::PENDING)->countAllResults()],
            ['group' => 'Sales', 'key' => 'bookings', 'route' => 'admin.bookings', 'label' => 'Bookings', 'icon' => 'calendar', 'new' => model(BookingModel::class)->where('status', Booking::PENDING)->countAllResults()],
            ['group' => 'Marketing', 'key' => 'offers', 'route' => 'admin.offers', 'label' => 'Festival offers', 'icon' => 'percent', 'new' => 0],
            ['group' => 'Marketing', 'key' => 'vouchers', 'route' => 'admin.vouchers', 'label' => 'Vouchers', 'icon' => 'ticket', 'new' => 0],
            ['group' => 'Catalogue', 'key' => 'products', 'route' => 'admin.products', 'label' => 'Products', 'icon' => 'products', 'new' => 0],
            ['group' => 'Catalogue', 'key' => 'categories', 'route' => 'admin.categories', 'label' => 'Categories', 'icon' => 'categories', 'new' => 0],
            ['group' => 'Catalogue', 'key' => 'options', 'route' => 'admin.options', 'label' => 'Colours and sizes', 'icon' => 'hanger', 'new' => 0],
        ];
    }
}
