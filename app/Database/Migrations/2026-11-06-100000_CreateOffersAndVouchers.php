<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Festival offers apply by themselves between two dates. Vouchers are codes a
 * shopper enters. Orders copy what they received, so later edits never rewrite one.
 */
class CreateOffersAndVouchers extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'name'           => ['type' => 'VARCHAR', 'constraint' => 100],
            'discount_type'  => ['type' => 'VARCHAR', 'constraint' => 10],
            // A whole percent, or an amount in paise.
            'discount_value' => ['type' => 'INT', 'unsigned' => true],
            'min_order'      => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'starts_on'      => ['type' => 'DATE'],
            'ends_on'        => ['type' => 'DATE'],
            'is_active'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['is_active', 'starts_on', 'ends_on']);
        $this->forge->createTable('offers');

        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'code'           => ['type' => 'VARCHAR', 'constraint' => 24],
            'batch'          => ['type' => 'VARCHAR', 'constraint' => 16],
            'name'           => ['type' => 'VARCHAR', 'constraint' => 100],
            'discount_type'  => ['type' => 'VARCHAR', 'constraint' => 10],
            'discount_value' => ['type' => 'INT', 'unsigned' => true],
            'min_order'      => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'expires_on'     => ['type' => 'DATE', 'null' => true],
            'max_uses'       => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'used_count'     => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'is_active'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('code');
        $this->forge->addKey('batch');
        $this->forge->createTable('vouchers');

        $this->forge->addColumn('orders', [
            'discount'       => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'discount_label' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'voucher_id'     => ['type' => 'INT', 'unsigned' => true, 'null' => true],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('orders', ['discount', 'discount_label', 'voucher_id']);
        $this->forge->dropTable('vouchers');
        $this->forge->dropTable('offers');
    }
}
