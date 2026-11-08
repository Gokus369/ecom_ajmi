<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Entities\Category;
use App\Models\CategoryModel;
use App\Models\ProductModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;

class Categories extends BaseController
{
    public function index(): string
    {
        return view('admin/categories/index', [
            'title'      => 'Categories',
            'categories' => model(CategoryModel::class)->withProductCounts(),
        ]);
    }

    public function create(): RedirectResponse
    {
        $categories = model(CategoryModel::class);
        $data       = $this->formData();

        if ($categories->insert($data) === false) {
            return redirect()->route('admin.categories')->withInput()->with('errors', $categories->errors());
        }

        return redirect()->route('admin.categories')->with('message', "{$data['name']} added.");
    }

    public function edit(int $id): string
    {
        $category = $this->findOrFail($id);

        return view('admin/categories/edit', [
            'title'    => "Edit {$category->name}",
            'category' => $category,
        ]);
    }

    public function update(int $id): RedirectResponse
    {
        $this->findOrFail($id);

        $categories = model(CategoryModel::class);
        $data       = $this->formData();

        if ($categories->update($id, $data) === false) {
            return redirect()->route('admin.categories.edit', [$id])->withInput()->with('errors', $categories->errors());
        }

        return redirect()->route('admin.categories')->with('message', "{$data['name']} saved.");
    }

    public function delete(int $id): RedirectResponse
    {
        $category = $this->findOrFail($id);
        $inUse    = model(ProductModel::class)->where('category_id', $id)->countAllResults();

        if ($inUse > 0) {
            return redirect()->route('admin.categories')
                ->with('error', "{$category->name} still has {$inUse} product(s). Move or delete them first.");
        }

        model(CategoryModel::class)->delete($id);

        return redirect()->route('admin.categories')->with('message', "{$category->name} deleted.");
    }

    private function findOrFail(int $id): Category
    {
        $category = model(CategoryModel::class)->find($id);

        if ($category === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $category;
    }

    /**
     * @return array{name: string, sort_order: int}
     */
    private function formData(): array
    {
        return [
            'name'       => trim((string) $this->request->getPost('name')),
            'sort_order' => (int) $this->request->getPost('sort_order'),
        ];
    }
}
