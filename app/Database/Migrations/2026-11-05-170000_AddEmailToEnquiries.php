<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEmailToEnquiries extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('enquiries', [
            'email' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('enquiries', 'email');
    }
}
