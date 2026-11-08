<?php

namespace App\Controllers;

use App\Libraries\FrameSequence;
use App\Models\CategoryModel;
use App\Models\ProductModel;

class Home extends BaseController
{
    public function index(): string
    {
        $products = model(ProductModel::class);

        return view('home/index', [
            'covers'     => $products->coversByCategory(),
            'frames'     => (new FrameSequence())->sets(),
            'products'   => $products->published()->sorted('newest')->findAll(8),
            'categories' => model(CategoryModel::class)->ordered()->findAll(),
        ]);
    }
}
