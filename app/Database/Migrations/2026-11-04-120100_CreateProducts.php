<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProducts extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'category_id'  => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'name'         => ['type' => 'VARCHAR', 'constraint' => 140],
            'slug'         => ['type' => 'VARCHAR', 'constraint' => 160],
            'summary'      => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'description'  => ['type' => 'TEXT', 'null' => true],
            'price'        => ['type' => 'INT', 'unsigned' => true],                 // minor units (paise)
            'stock'        => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'image'        => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'is_published' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('slug');
        $this->forge->addKey('category_id');
        $this->forge->addKey(['is_published', 'created_at']);
        $this->forge->addKey('price');
        $this->forge->addKey('stock');
        // Deleting a category leaves its products in place, uncategorised.
        $this->forge->addForeignKey('category_id', 'categories', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('products');
    }

    public function down(): void
    {
        $this->forge->dropTable('products');
    }
}
