<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBookings extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'status'        => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'pending'],
            'customer_name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'phone'         => ['type' => 'VARCHAR', 'constraint' => 20],
            'wedding_date'  => ['type' => 'DATE'],
            'ready_by'      => ['type' => 'VARCHAR', 'constraint' => 5, 'null' => true],
            'location'      => ['type' => 'VARCHAR', 'constraint' => 255],
            'people'        => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'notes'         => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['status', 'wedding_date']);
        $this->forge->addKey('wedding_date');
        $this->forge->createTable('bookings');
    }

    public function down(): void
    {
        $this->forge->dropTable('bookings');
    }
}
