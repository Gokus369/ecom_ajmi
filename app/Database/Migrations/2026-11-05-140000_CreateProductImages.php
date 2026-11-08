<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * The extra photos of a piece, in display order. products.image stays the main
 * photo, which is the one cards, the cart and orders show.
 */
class CreateProductImages extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'product_id' => ['type' => 'INT', 'unsigned' => true],
            'image'      => ['type' => 'VARCHAR', 'constraint' => 120],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('product_id');
        $this->forge->addForeignKey('product_id', 'products', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('product_images');
    }

    public function down(): void
    {
        $this->forge->dropTable('product_images');
    }
}
