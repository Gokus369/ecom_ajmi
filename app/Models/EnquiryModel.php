<?php

namespace App\Models;

use App\Entities\Enquiry;
use CodeIgniter\Model;

class EnquiryModel extends Model
{
    protected $table         = 'enquiries';
    protected $primaryKey    = 'id';
    protected $returnType    = Enquiry::class;
    protected $useTimestamps = true;
    protected $allowedFields = ['type', 'status', 'customer_name', 'phone', 'email', 'event_date', 'details', 'source', 'order_value'];

    public function newest(): static
    {
        return $this->orderBy('created_at', 'DESC')->orderBy('id', 'DESC');
    }

    /**
     * @return array<string, int> enquiry count per status, for the admin filter tabs
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
