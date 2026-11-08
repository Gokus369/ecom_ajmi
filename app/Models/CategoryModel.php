<?php

namespace App\Models;

use App\Entities\Category;
use App\Models\Traits\Sluggable;
use CodeIgniter\Model;

class CategoryModel extends Model
{
    use Sluggable;

    protected $table         = 'categories';
    protected $primaryKey    = 'id';
    protected $returnType    = Category::class;
    protected $useTimestamps = true;
    protected $allowedFields = ['name', 'slug', 'sort_order'];

    protected $validationRules = [
        'name'       => 'required|max_length[80]',
        'sort_order' => 'permit_empty|integer',
    ];
    protected $validationMessages = [
        'name' => [
            'required'   => 'Please give the category a name.',
            'max_length' => 'Category names can be up to 80 characters.',
        ],
    ];

    protected $beforeInsert = ['addSlug'];

    public function ordered(): static
    {
        return $this->orderBy('sort_order', 'ASC')->orderBy('name', 'ASC');
    }

    public function findBySlug(string $slug): ?Category
    {
        return $this->where('slug', $slug)->first();
    }

    /**
     * @return list<Category> each with a product_count of its undeleted products
     */
    public function withProductCounts(): array
    {
        return $this->select('categories.*')
            ->selectCount('products.id', 'product_count')
            ->join('products', 'products.category_id = categories.id AND products.deleted_at IS NULL', 'left')
            ->groupBy('categories.id')
            ->ordered()
            ->findAll();
    }
}
