<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * What the two-week review needs that the site did not record: where a customer
 * came from, what an enquiry sold for, and the saves and shares on each post.
 */
class AddAnalytics extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('enquiries', [
            'source'      => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            // In paise. Set once the enquiry turns into a sale made outside the site.
            'order_value' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
        ]);
        $this->forge->addColumn('orders', [
            'heard_from' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
        ]);

        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'title'      => ['type' => 'VARCHAR', 'constraint' => 140],
            'posted_on'  => ['type' => 'DATE'],
            'saves'      => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'shares'     => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('posted_on');
        $this->forge->createTable('post_stats');
    }

    public function down(): void
    {
        $this->forge->dropTable('post_stats');
        $this->forge->dropColumn('orders', 'heard_from');
        $this->forge->dropColumn('enquiries', ['source', 'order_value']);
    }
}
