<?php

namespace App\Models;

use App\Entities\OrderItem;
use CodeIgniter\Model;

class OrderItemModel extends Model
{
    protected $table         = 'order_items';
    protected $primaryKey    = 'id';
    protected $returnType    = OrderItem::class;
    protected $allowedFields = ['order_id', 'product_id', 'variant_id', 'name', 'colour', 'size', 'unit_price', 'quantity', 'line_total'];

    /**
     * @return list<OrderItem>
     */
    public function forOrder(int $orderId): array
    {
        return $this->where('order_id', $orderId)->orderBy('id', 'ASC')->findAll();
    }
}
