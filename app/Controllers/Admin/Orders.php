<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Entities\Enquiry;
use App\Entities\Order;
use App\Models\OrderItemModel;
use App\Models\OrderModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;

class Orders extends BaseController
{
    private const PER_PAGE = 25;

    public function index(): string
    {
        $orders = model(OrderModel::class);
        $counts = $orders->countsByStatus();
        $term   = $this->searchTerm();
        $status = $this->request->getGet('status');
        $status = is_string($status) && isset(Order::STATUS_LABELS[$status]) ? $status : null;

        if ($status !== null) {
            $orders->where('status', $status);
        }

        if ($term !== '') {
            $orders->search($term);
        }

        return view('admin/orders/index', [
            'title'  => 'Orders',
            'term'   => $term,
            'status' => $status,
            'counts' => $counts,
            'orders' => $orders->newest()->paginate(self::PER_PAGE),
            'pager'  => $orders->pager,
        ]);
    }

    public function show(int $id): string
    {
        $order = $this->findOrFail($id);

        return view('admin/orders/show', [
            'title' => "Order {$order->number}",
            'order' => $order,
            'items' => model(OrderItemModel::class)->forOrder($order->id),
        ]);
    }

    /** Where the customer came from, for the two-week review. */
    public function source(int $id): RedirectResponse
    {
        $this->findOrFail($id);

        $source = (string) $this->request->getPost('heard_from');

        if ($source !== '' && ! isset(Enquiry::SOURCE_LABELS[$source])) {
            return redirect()->route('admin.orders.show', [$id])->with('error', 'Please choose from the list.');
        }

        model(OrderModel::class)->update($id, ['heard_from' => $source === '' ? null : $source]);

        return redirect()->route('admin.orders.show', [$id])->with('message', 'Saved.');
    }

    public function updateStatus(int $id): RedirectResponse
    {
        $order  = $this->findOrFail($id);
        $status = (string) $this->request->getPost('status');

        if (! $order->canMoveTo($status)) {
            return redirect()->route('admin.orders.show', [$id])
                ->with('error', 'That order has already moved on. Please check its status and try again.');
        }

        service('orders')->moveTo($order, $status);

        return redirect()->route('admin.orders.show', [$id])
            ->with('message', "Order {$order->number} is now " . strtolower(Order::STATUS_LABELS[$status]) . '.');
    }

    private function findOrFail(int $id): Order
    {
        $order = model(OrderModel::class)->find($id);

        if ($order === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $order;
    }
}
