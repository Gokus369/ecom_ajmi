<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * A piece can come in several colours and sizes, kept on the product as comma-separated
 * lists. Order items copy the shopper's choice, so later edits never rewrite an order.
 */
class AddColoursAndSizes extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('products', [
            'colours' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'sizes'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        ]);
        $this->forge->addColumn('order_items', [
            'colour' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'size'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('order_items', ['colour', 'size']);
        $this->forge->dropColumn('products', ['colours', 'sizes']);
    }
}
