<?php

namespace App\Libraries;

use App\Entities\Order;
use App\Entities\Product;
use App\Exceptions\OutOfStock;
use App\Exceptions\VoucherUnavailable;
use App\Models\OrderItemModel;
use App\Models\OrderModel;
use App\Models\ProductModel;
use App\Models\VoucherModel;
use InvalidArgumentException;
use Throwable;

class OrderService
{
    /** No 0/O or 1/I/L, so a number survives being read out over the phone. */
    private const NUMBER_ALPHABET = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';
    private const NUMBER_LENGTH   = 10;

    public function __construct(
        private readonly OrderModel $orders,
        private readonly OrderItemModel $items,
        private readonly ProductModel $products,
        private readonly VoucherModel $vouchers,
    ) {
    }

    /**
     * Writes the order, takes its stock and spends its voucher as one transaction:
     * either all of it happens or none of it does.
     *
     * @param array<string, string|null>                                                       $customer validated checkout fields
     * @param list<array{product: Product, variant_id?: int|null, colour: string, size: string, quantity: int, total: int}> $lines
     * @param array{subtotal: int, discount?: int, discount_label?: string|null, voucher_id?: int|null, shipping: int, total: int} $totals
     *
     * @throws OutOfStock
     * @throws VoucherUnavailable
     */
    public function place(array $customer, array $lines, array $totals): Order
    {
        $db = db_connect();
        $db->transException(true)->transStart();

        try {
            $voucherId = $totals['voucher_id'] ?? null;

            if ($voucherId !== null && ! $this->vouchers->redeem($voucherId)) {
                throw new VoucherUnavailable();
            }

            foreach ($lines as $line) {
                if (! $this->products->takeStock($line['product']->id, $line['quantity'], $line['variant_id'] ?? null)) {
                    throw new OutOfStock($line['product']->name);
                }
            }

            $orderId = $this->orders->insert([
                ...$customer,
                ...$totals,
                'number' => $this->newNumber(),
                'status' => Order::PENDING,
            ], true);

            $this->items->insertBatch(array_map(static fn (array $line): array => [
                'order_id'   => $orderId,
                'product_id' => $line['product']->id,
                'variant_id' => $line['variant_id'] ?? null,
                'name'       => $line['product']->name,
                'colour'     => $line['colour'] === '' ? null : $line['colour'],
                'size'       => $line['size'] === '' ? null : $line['size'],
                'unit_price' => $line['product']->price,
                'quantity'   => $line['quantity'],
                'line_total' => $line['total'],
            ], $lines));

            $db->transComplete();
        } catch (Throwable $e) {
            $db->transRollback();

            throw $e;
        }

        return $this->orders->find($orderId);
    }

    /**
     * Moves an order on a step. Cancelling puts its stock back on the shelf and
     * gives its voucher back.
     *
     * @throws InvalidArgumentException when the workflow does not allow the step
     */
    public function moveTo(Order $order, string $status): void
    {
        if (! $order->canMoveTo($status)) {
            throw new InvalidArgumentException("An order cannot go from {$order->status} to {$status}.");
        }

        $db = db_connect();
        $db->transException(true)->transStart();

        try {
            if ($status === Order::CANCELLED) {
                foreach ($this->items->forOrder($order->id) as $item) {
                    if ($item->product_id !== null) {
                        $this->products->returnStock($item->product_id, $item->quantity, $item->variant_id);
                    }
                }

                if ($order->voucher_id !== null) {
                    $this->vouchers->release((int) $order->voucher_id);
                }
            }

            $this->orders->update($order->id, ['status' => $status]);

            $db->transComplete();
        } catch (Throwable $e) {
            $db->transRollback();

            throw $e;
        }
    }

    private function newNumber(): string
    {
        $last   = strlen(self::NUMBER_ALPHABET) - 1;
        $number = '';

        for ($i = 0; $i < self::NUMBER_LENGTH; $i++) {
            $number .= self::NUMBER_ALPHABET[random_int(0, $last)];
        }

        return $number;
    }
}
