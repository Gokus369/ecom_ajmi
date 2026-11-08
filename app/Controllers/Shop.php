<?php

namespace App\Controllers;

use App\Entities\Category;
use App\Models\CategoryModel;
use App\Models\ProductModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Shop extends BaseController
{
    private const PER_PAGE = 12;

    public function index(): string
    {
        return $this->listing();
    }

    public function category(string $slug): string
    {
        $category = model(CategoryModel::class)->findBySlug($slug);

        if ($category === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $this->listing($category);
    }

    private function listing(?Category $category = null): string
    {
        $term = $this->searchTerm();
        $sort = $this->request->getGet('sort');
        $sort = is_string($sort) && isset(ProductModel::SORTS[$sort]) ? $sort : 'newest';

        $products = model(ProductModel::class)->published();

        if ($category !== null) {
            $products->where('category_id', $category->id);
        }

        if ($term !== '') {
            $products->search($term);
        }

        return view('shop/index', [
            'title'    => $category->name ?? ($term !== '' ? "Search: {$term}" : 'All products'),
            'category' => $category,
            'term'     => $term,
            'sort'     => $sort,
            'products' => $products->sorted($sort)->paginate(self::PER_PAGE),
            'pager'    => $products->pager,
        ]);
    }
}
