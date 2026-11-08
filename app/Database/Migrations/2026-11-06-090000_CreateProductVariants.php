<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Colours and sizes move from comma-separated text on each product to master lists,
 * and each combination on a piece gets its own stock. products.stock stays as the
 * total, so badges and lists can read it without adding up the variants.
 */
class CreateProductVariants extends Migration
{
    /** A starting point, changed in the admin under Colours and sizes. */
    private const COLOURS = [
        'Ivory'    => '#f4eee0',
        'Gold'     => '#c9a24b',
        'Rosewood' => '#9a4f50',
        'Rust red' => '#a23b2a',
        'Wine'     => '#5b1226',
        'Emerald'  => '#0f6b4f',
        'Black'    => '#1c1917',
    ];

    private const SIZES = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];

    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 40],
            'hex'        => ['type' => 'CHAR', 'constraint' => 7],
            'sort_order' => ['type' => 'INT', 'default' => 0],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('name');
        $this->forge->createTable('colours');

        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 40],
            'sort_order' => ['type' => 'INT', 'default' => 0],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('name');
        $this->forge->createTable('sizes');

        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'product_id' => ['type' => 'INT', 'unsigned' => true],
            'colour_id'  => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'size_id'    => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'stock'      => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['product_id', 'colour_id', 'size_id']);
        $this->forge->addForeignKey('product_id', 'products', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('colour_id', 'colours', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('size_id', 'sizes', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('product_variants');

        // A plain number, not a foreign key: an order line outlives the variant it sold.
        $this->forge->addColumn('order_items', [
            'variant_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
        ]);

        $colours = $this->seedColours();
        $sizes   = $this->seedSizes();

        $this->convertProducts($colours, $sizes);

        $this->dropColumn('products', 'colours');
        $this->dropColumn('products', 'sizes');
    }

    public function down(): void
    {
        $this->forge->addColumn('products', [
            'colours' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'sizes'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        ]);
        $this->dropColumn('order_items', 'variant_id');
        $this->forge->dropTable('product_variants');
        $this->forge->dropTable('sizes');
        $this->forge->dropTable('colours');
    }

    /**
     * Plain SQL because forge rebuilds the table on SQLite, which breaks the tables
     * that point at it. Both SQLite and MySQL can do this directly.
     */
    private function dropColumn(string $table, string $column): void
    {
        $this->db->query('ALTER TABLE ' . $this->db->prefixTable($table) . " DROP COLUMN {$column}");
    }

    /**
     * @return array<string, int> colour ID keyed by lower-case name
     */
    private function seedColours(): array
    {
        $ids   = [];
        $order = 0;

        foreach (self::COLOURS as $name => $hex) {
            $this->db->table('colours')->insert(['name' => $name, 'hex' => $hex, 'sort_order' => ++$order]);
            $ids[mb_strtolower($name)] = $this->db->insertID();
        }

        return $ids;
    }

    /**
     * @return array<string, int> size ID keyed by lower-case name
     */
    private function seedSizes(): array
    {
        $ids   = [];
        $order = 0;

        foreach (self::SIZES as $name) {
            $this->db->table('sizes')->insert(['name' => $name, 'sort_order' => ++$order]);
            $ids[mb_strtolower($name)] = $this->db->insertID();
        }

        return $ids;
    }

    /**
     * Each piece's old lists become variants. The old stock was one number for the
     * whole piece, so the first combination holds it all and the total stays the same.
     *
     * @param array<string, int> $colours
     * @param array<string, int> $sizes
     */
    private function convertProducts(array $colours, array $sizes): void
    {
        $products = $this->db->table('products')
            ->select('id, stock, colours, sizes')
            ->where('deleted_at', null)
            ->groupStart()->where('colours IS NOT NULL')->orWhere('sizes IS NOT NULL')->groupEnd()
            ->get()
            ->getResultArray();

        foreach ($products as $product) {
            $colourIds = $this->listIds('colours', $product['colours'], $colours);
            $sizeIds   = $this->listIds('sizes', $product['sizes'], $sizes);
            $stock     = (int) $product['stock'];

            foreach ($colourIds ?: [null] as $colourId) {
                foreach ($sizeIds ?: [null] as $sizeId) {
                    if ($colourId === null && $sizeId === null) {
                        continue;
                    }

                    $this->db->table('product_variants')->insert([
                        'product_id' => $product['id'],
                        'colour_id'  => $colourId,
                        'size_id'    => $sizeId,
                        'stock'      => $stock,
                    ]);

                    $stock = 0;
                }
            }
        }
    }

    /**
     * Finds each name on a comma-separated list, adding any the master list lacks.
     *
     * @param array<string, int> $known
     *
     * @return list<int>
     */
    private function listIds(string $table, ?string $text, array &$known): array
    {
        $ids = [];

        foreach (preg_split('/[,\r\n]+/', (string) $text) as $name) {
            $name = trim($name);
            $key  = mb_strtolower($name);

            if ($name === '') {
                continue;
            }

            if (! isset($known[$key])) {
                $row = ['name' => mb_substr($name, 0, 40), 'sort_order' => count($known) + 1];

                $this->db->table($table)->insert($table === 'colours' ? [...$row, 'hex' => '#a8a29e'] : $row);
                $known[$key] = $this->db->insertID();
            }

            $ids[$known[$key]] = $known[$key];
        }

        return array_values($ids);
    }
}
