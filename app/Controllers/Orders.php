<?php

namespace App\Controllers;

use App\Models\OrderItemModel;
use App\Models\OrderModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Orders extends BaseController
{
    /**
     * The random order number is what protects this page, so customers can
     * check on an order without an account. Keep it out of search engines.
     */
    public function show(string $number): string
    {
        $order = model(OrderModel::class)->findByNumber($number);

        if ($order === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return view('orders/show', [
            'title'   => "Order {$order->number}",
            'noindex' => true,
            'order'   => $order,
            'items'   => model(OrderItemModel::class)->forOrder($order->id),
        ]);
    }
}
