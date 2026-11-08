<?php

namespace App\Models;

use App\Entities\Booking;
use CodeIgniter\Model;

class BookingModel extends Model
{
    protected $table         = 'bookings';
    protected $primaryKey    = 'id';
    protected $returnType    = Booking::class;
    protected $useTimestamps = true;
    protected $allowedFields = [
        'status', 'customer_name', 'phone', 'wedding_date', 'ready_by', 'location', 'people', 'notes',
    ];

    public function soonestFirst(): static
    {
        return $this->orderBy('wedding_date', 'ASC')->orderBy('id', 'ASC');
    }

    /**
     * Weddings still to come. $today is a Y-m-d date in the store's timezone.
     *
     * @return list<Booking>
     */
    public function upcoming(string $today, int $limit): array
    {
        return $this->whereIn('status', [Booking::PENDING, Booking::CONFIRMED])
            ->where('wedding_date >=', $today)
            ->soonestFirst()
            ->findAll($limit);
    }

    /**
     * @return array<string, int> booking count per status, for the admin filter tabs
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
}
