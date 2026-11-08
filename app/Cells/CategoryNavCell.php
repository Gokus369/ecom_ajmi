<?php

namespace App\Cells;

use App\Entities\Category;
use App\Models\CategoryModel;
use CodeIgniter\View\Cells\Cell;

/**
 * The category links under the store header, with the current page marked.
 */
class CategoryNavCell extends Cell
{
    /** @var list<Category> */
    public array $categories = [];

    public string $current = '';

    public function mount(): void
    {
        $this->categories = model(CategoryModel::class)->ordered()->findAll();
        $this->current    = uri_string();
    }
}
