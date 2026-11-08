<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateOrders extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'number'         => ['type' => 'VARCHAR', 'constraint' => 16],
            'status'         => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'pending'],
            'customer_name'  => ['type' => 'VARCHAR', 'constraint' => 100],
            'phone'          => ['type' => 'VARCHAR', 'constraint' => 20],
            'email'          => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'address'        => ['type' => 'VARCHAR', 'constraint' => 255],
            'city'           => ['type' => 'VARCHAR', 'constraint' => 80],
            'state'          => ['type' => 'VARCHAR', 'constraint' => 80],
            'postcode'       => ['type' => 'VARCHAR', 'constraint' => 12],
            'notes'          => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'payment_method' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'cod'],
            'subtotal'       => ['type' => 'INT', 'unsigned' => true],
            'shipping'       => ['type' => 'INT', 'unsigned' => true],
            'total'          => ['type' => 'INT', 'unsigned' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('number');
        $this->forge->addKey(['status', 'created_at']);
        $this->forge->addKey('created_at');
        $this->forge->createTable('orders');
    }

    public function down(): void
    {
        $this->forge->dropTable('orders');
    }
}
