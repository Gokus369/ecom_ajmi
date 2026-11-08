<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * A master list that pieces choose from: colours or sizes. The list is kept in one
 * place, so a name is never typed twice or spelt two ways.
 */
abstract class OptionListModel extends Model
{
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    /** The column on product_variants that points at this list. */
    protected string $variantColumn;

    public function ordered(): static
    {
        return $this->orderBy("{$this->table}.sort_order", 'ASC')->orderBy("{$this->table}.id", 'ASC');
    }

    /**
     * @return list<object> each with a pieces count of the products using it
     */
    public function withPieceCounts(): array
    {
        $variants = $this->db->prefixTable('product_variants');

        return $this->select("{$this->table}.*")
            ->select("COUNT(DISTINCT {$variants}.product_id) AS pieces", false)
            ->join('product_variants', "product_variants.{$this->variantColumn} = {$this->table}.id", 'left')
            ->groupBy("{$this->table}.id")
            ->ordered()
            ->findAll();
    }

    public function piecesUsing(int $id): int
    {
        return (int) $this->db->table('product_variants')
            ->select('COUNT(DISTINCT product_id) AS pieces')
            ->where($this->variantColumn, $id)
            ->get()
            ->getRow()
            ->pieces;
    }
}
