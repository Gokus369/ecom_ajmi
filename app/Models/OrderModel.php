<?php

namespace App\Models;

use App\Entities\Order;
use CodeIgniter\Model;

class OrderModel extends Model
{
    protected $table         = 'orders';
    protected $primaryKey    = 'id';
    protected $returnType    = Order::class;
    protected $useTimestamps = true;
    protected $allowedFields = [
        'number', 'status', 'customer_name', 'phone', 'email', 'address', 'city', 'state',
        'postcode', 'notes', 'payment_method', 'subtotal', 'shipping', 'total', 'user_id',
        'discount', 'discount_label', 'voucher_id', 'heard_from',
    ];

    public function findByNumber(string $number): ?Order
    {
        return $this->where('number', $number)->first();
    }

    public function newest(): static
    {
        return $this->orderBy('created_at', 'DESC')->orderBy('id', 'DESC');
    }

    public function search(string $term): static
    {
        return $this->groupStart()
            ->like('number', $term)
            ->orLike('customer_name', $term)
            ->orLike('phone', $term)
            ->groupEnd();
    }

    /**
     * @return array<string, int> order count per status, for the admin filter tabs
     */
    public function countsByStatus(): array
    {
        $rows = $this->builder()
            ->select('status, COUNT(id) AS total')
            ->groupBy('status')
            ->get()
            ->getResultArray();

        return array_column($rows, 'total', 'status');
    }

    /**
     * Sales since a UTC date, leaving out cancelled orders.
     */
    public function revenueSince(string $date): int
    {
        $row = $this->builder()
            ->selectSum('total')
            ->where('status !=', Order::CANCELLED)
            ->where('created_at >=', $date)
            ->get()
            ->getRowArray();

        return (int) ($row['total'] ?? 0);
    }
}
