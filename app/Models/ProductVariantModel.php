<?php

namespace App\Models;

use App\Entities\ProductVariant;
use CodeIgniter\Model;
use Throwable;

class ProductVariantModel extends Model
{
    protected $table         = 'product_variants';
    protected $primaryKey    = 'id';
    protected $returnType    = ProductVariant::class;
    protected $allowedFields = ['product_id', 'colour_id', 'size_id', 'stock'];

    /**
     * Every combination of these pieces with its names, in master list order.
     *
     * @param list<int> $productIds
     *
     * @return array<int, list<ProductVariant>> keyed by product ID
     */
    public function forProducts(array $productIds): array
    {
        $rows = $this->select('product_variants.*, colours.name AS colour, colours.hex AS colour_hex, sizes.name AS size')
            ->join('colours', 'colours.id = product_variants.colour_id', 'left')
            ->join('sizes', 'sizes.id = product_variants.size_id', 'left')
            ->whereIn('product_variants.product_id', $productIds)
            ->orderBy('colours.sort_order', 'ASC')
            ->orderBy('colours.id', 'ASC')
            ->orderBy('sizes.sort_order', 'ASC')
            ->orderBy('sizes.id', 'ASC')
            ->findAll();

        $grouped = [];

        foreach ($rows as $row) {
            $grouped[$row->product_id][] = $row;
        }

        return $grouped;
    }

    /**
     * Makes a piece's combinations match these rows, then updates its total. A
     * combination that stays keeps its ID, so orders still point at it.
     *
     * @param list<array{colour_id: int|null, size_id: int|null, stock: int}> $rows
     */
    public function saveFor(int $productId, array $rows): void
    {
        $this->db->transException(true)->transStart();

        try {
            $existing = [];

            foreach ($this->db->table($this->table)->where('product_id', $productId)->get()->getResultArray() as $row) {
                $existing[$row['colour_id'] . '|' . $row['size_id']] = (int) $row['id'];
            }

            foreach ($rows as $row) {
                $key = $row['colour_id'] . '|' . $row['size_id'];

                if (isset($existing[$key])) {
                    $this->db->table($this->table)->where('id', $existing[$key])->update(['stock' => $row['stock']]);
                    unset($existing[$key]);
                } else {
                    $this->db->table($this->table)->insert(['product_id' => $productId, ...$row]);
                }
            }

            if ($existing !== []) {
                $this->db->table($this->table)->whereIn('id', array_values($existing))->delete();
            }

            model(ProductModel::class)->syncStock($productId);

            $this->db->transComplete();
        } catch (Throwable $e) {
            $this->db->transRollback();

            throw $e;
        }
    }
}
