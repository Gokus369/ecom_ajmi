<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Customers can sign up. Email addresses are not verified, so an order belongs to
 * the account it was placed from, never to whoever signs up with a matching address.
 */
class AddCustomerAccounts extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('users', [
            'name' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
        ]);
        $this->forge->addColumn('orders', [
            'user_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
        ]);
        $this->forge->addKey('user_id');
        $this->forge->processIndexes('orders');
    }

    public function down(): void
    {
        $this->forge->dropColumn('orders', 'user_id');
        $this->forge->dropColumn('users', 'name');
    }
}
