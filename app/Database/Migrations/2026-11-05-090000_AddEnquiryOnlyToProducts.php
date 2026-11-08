<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Bespoke and bridal pieces have no fixed price, so they are enquired about
 * rather than bought: no cart, no price, and stock does not apply.
 */
class AddEnquiryOnlyToProducts extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('products', [
            'is_enquiry_only' => ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 0],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('products', 'is_enquiry_only');
    }
}
