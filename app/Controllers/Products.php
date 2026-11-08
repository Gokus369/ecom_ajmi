<?php

namespace App\Controllers;

use App\Models\CategoryModel;
use App\Models\ProductModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Products extends BaseController
{
    public function show(string $slug): string
    {
        $products = model(ProductModel::class);
        $product  = $products->findPublishedBySlug($slug);

        if ($product === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return view('products/show', [
            'title'           => $product->name,
            'metaDescription' => $product->summary,
            'product'         => $product,
            'photos'          => $products->photosOf($product),
            'category'        => $product->category_id ? model(CategoryModel::class)->find($product->category_id) : null,
            'related'         => $products->related($product),
        ]);
    }
}
