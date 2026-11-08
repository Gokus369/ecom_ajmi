<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateOrderItems extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'order_id'   => ['type' => 'INT', 'unsigned' => true],
            'product_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            // Name and price are copied from the product so later edits never rewrite an order.
            'name'       => ['type' => 'VARCHAR', 'constraint' => 140],
            'unit_price' => ['type' => 'INT', 'unsigned' => true],
            'quantity'   => ['type' => 'INT', 'unsigned' => true],
            'line_total' => ['type' => 'INT', 'unsigned' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('order_id');
        $this->forge->addKey('product_id');
        $this->forge->addForeignKey('order_id', 'orders', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('product_id', 'products', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('order_items');
    }

    public function down(): void
    {
        $this->forge->dropTable('order_items');
    }
}
