<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Custom styling and bridal consultation requests from the bespoke page.
 */
class CreateEnquiries extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'type'          => ['type' => 'VARCHAR', 'constraint' => 20],
            'status'        => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'pending'],
            'customer_name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'phone'         => ['type' => 'VARCHAR', 'constraint' => 20],
            'event_date'    => ['type' => 'DATE', 'null' => true],
            'details'       => ['type' => 'TEXT', 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['status', 'created_at']);
        $this->forge->addKey('created_at');
        $this->forge->createTable('enquiries');
    }

    public function down(): void
    {
        $this->forge->dropTable('enquiries');
    }
}
