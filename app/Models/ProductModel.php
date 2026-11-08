<?php

namespace App\Models;

use App\Entities\Product;
use App\Models\Traits\Sluggable;
use CodeIgniter\Model;
use Throwable;

class ProductModel extends Model
{
    use Sluggable;

    /** Sort options offered to shoppers. Anything else in the URL falls back to the default. */
    public const SORTS = [
        'newest'     => ['label' => 'Newest', 'column' => 'created_at', 'direction' => 'DESC'],
        'price-asc'  => ['label' => 'Price: low to high', 'column' => 'price', 'direction' => 'ASC'],
        'price-desc' => ['label' => 'Price: high to low', 'column' => 'price', 'direction' => 'DESC'],
    ];

    protected $table          = 'products';
    protected $primaryKey     = 'id';
    protected $returnType     = Product::class;
    protected $useTimestamps  = true;
    protected $useSoftDeletes = true;
    protected $allowedFields  = [
        'category_id', 'name', 'slug', 'summary', 'description', 'price', 'stock', 'image', 'is_published',
        'is_enquiry_only',
    ];

    protected $validationRules = [
        'category_id' => 'permit_empty|is_natural_no_zero|is_not_unique[categories.id]',
        'name'        => 'required|max_length[140]',
        'summary'     => 'permit_empty|max_length[200]',
        'price'       => 'required|is_natural',
        'stock'       => 'required|is_natural',
    ];

    protected $beforeInsert = ['addSlug'];
    protected $afterFind    = ['attachVariants'];
    protected $afterDelete  = ['removeVariants'];

    /** Only what shoppers may see. Chain like $products->published()->paginate(12) */
    public function published(): static
    {
        return $this->where('is_published', 1);
    }

    public function sorted(string $sort): static
    {
        $option = self::SORTS[$sort] ?? self::SORTS['newest'];

        // Pieces with no price would otherwise all sort as the cheapest.
        if ($option['column'] === 'price') {
            $this->orderBy('is_enquiry_only', 'ASC');
        }

        return $this->orderBy($option['column'], $option['direction'])->orderBy('id', 'DESC');
    }

    public function search(string $term): static
    {
        return $this->groupStart()
            ->like('products.name', $term)
            ->orLike('products.summary', $term)
            ->groupEnd();
    }

    /** Adds category_name to each row, for the admin product table. */
    public function withCategoryName(): static
    {
        return $this->select('products.*, categories.name AS category_name')
            ->join('categories', 'categories.id = products.category_id', 'left');
    }

    public function findPublishedBySlug(string $slug): ?Product
    {
        return $this->published()->where('slug', $slug)->first();
    }

    /**
     * Every photo of a piece, the main one first.
     *
     * @return list<string> file names
     */
    public function photosOf(Product $product): array
    {
        $extras = $this->db->table('product_images')
            ->select('image')
            ->where('product_id', $product->id)
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        return array_values(array_filter([$product->image, ...array_column($extras, 'image')]));
    }

    /**
     * Saves a piece's photos in order. The first becomes the main photo.
     *
     * @param list<string> $photos file names
     */
    public function setPhotos(int $id, array $photos): void
    {
        $this->db->transException(true)->transStart();

        try {
            $this->db->table($this->table)->where('id', $id)->update(['image' => $photos[0] ?? null]);
            $this->db->table('product_images')->where('product_id', $id)->delete();

            if (count($photos) > 1) {
                $this->db->table('product_images')->insertBatch(array_map(
                    static fn (string $image): array => ['product_id' => $id, 'image' => $image],
                    array_slice($photos, 1),
                ));
            }

            $this->db->transComplete();
        } catch (Throwable $e) {
            $this->db->transRollback();

            throw $e;
        }
    }

    /**
     * @return list<Product>
     */
    public function related(Product $product, int $limit = 4): array
    {
        if ($product->category_id === null) {
            return [];
        }

        return $this->published()
            ->where('category_id', $product->category_id)
            ->where('id !=', $product->id)
            ->sorted('newest')
            ->findAll($limit);
    }

    /**
     * The newest published piece with a photo in each category, to stand in
     * for the category on the home page.
     *
     * @return array<int, Product> keyed by category ID
     */
    public function coversByCategory(): array
    {
        $covers = [];

        foreach ($this->published()->where('image IS NOT NULL')->where('category_id IS NOT NULL')->sorted('newest')->findAll(200) as $product) {
            $covers[$product->category_id] ??= $product;
        }

        return $covers;
    }

    /**
     * @return list<Product>
     */
    public function lowStock(int $threshold, int $limit = 8): array
    {
        return $this->published()
            ->where('is_enquiry_only', 0)
            ->where('stock <=', $threshold)
            ->orderBy('stock', 'ASC')
            ->findAll($limit);
    }

    /**
     * Takes stock only if enough is left, as one atomic statement each, so two
     * shoppers can never both buy the last item. Call inside a transaction when
     * there is a variant, because a piece's total is taken as well.
     */
    public function takeStock(int $id, int $quantity, ?int $variantId = null): bool
    {
        if ($variantId !== null) {
            $this->db->query(
                'UPDATE ' . $this->db->prefixTable('product_variants') . ' SET stock = stock - ? WHERE id = ? AND product_id = ? AND stock >= ?',
                [$quantity, $variantId, $id, $quantity],
            );

            if ($this->db->affectedRows() !== 1) {
                return false;
            }
        }

        $this->db->query(
            'UPDATE ' . $this->db->prefixTable($this->table) . ' SET stock = stock - ? WHERE id = ? AND stock >= ? AND is_published = 1 AND is_enquiry_only = 0 AND deleted_at IS NULL',
            [$quantity, $id, $quantity],
        );

        return $this->db->affectedRows() === 1;
    }

    /**
     * Puts stock back. A variant removed since the sale takes nothing back, and the
     * total then follows what the remaining variants hold.
     */
    public function returnStock(int $id, int $quantity, ?int $variantId = null): void
    {
        $this->db->query(
            $variantId === null
                ? 'UPDATE ' . $this->db->prefixTable($this->table) . ' SET stock = stock + ? WHERE id = ?'
                : 'UPDATE ' . $this->db->prefixTable('product_variants') . ' SET stock = stock + ? WHERE id = ?',
            [$quantity, $variantId ?? $id],
        );

        $this->syncStock($id);
    }

    /**
     * Sets a piece's total to what its variants hold. Pieces with none keep theirs.
     */
    public function syncStock(int $id): void
    {
        $variants = $this->db->prefixTable('product_variants');

        $this->db->query(
            'UPDATE ' . $this->db->prefixTable($this->table) . " SET stock = (SELECT SUM(stock) FROM {$variants} WHERE product_id = ?) WHERE id = ? AND EXISTS (SELECT 1 FROM {$variants} WHERE product_id = ?)",
            [$id, $id, $id],
        );
    }

    /**
     * Gives every piece read its variants, with one query however many were found.
     *
     * @param array{data: mixed, singleton: bool} $data
     *
     * @return array{data: mixed, singleton: bool}
     */
    protected function attachVariants(array $data): array
    {
        $found = array_filter($data['singleton'] ? [$data['data']] : (array) $data['data'], static fn ($row): bool => $row instanceof Product);

        if ($found !== []) {
            $variants = model(ProductVariantModel::class)->forProducts(array_map(static fn (Product $product): int => $product->id, array_values($found)));

            foreach ($found as $product) {
                $product->setVariants($variants[$product->id] ?? []);
            }
        }

        return $data;
    }

    /**
     * A deleted piece is kept for past orders, which hold their own copy of the
     * choice, but it stops holding colours and sizes on the master lists.
     *
     * @param array{id: int|list<int>} $data
     *
     * @return array{id: int|list<int>}
     */
    protected function removeVariants(array $data): array
    {
        $this->db->table('product_variants')->whereIn('product_id', (array) $data['id'])->delete();

        return $data;
    }
}
