<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Entities\Product;
use App\Libraries\ProductImages;
use App\Models\CategoryModel;
use App\Models\ColourModel;
use App\Models\OptionListModel;
use App\Models\ProductModel;
use App\Models\ProductVariantModel;
use App\Models\SizeModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\HTTP\RedirectResponse;
use Throwable;

class Products extends BaseController
{
    private const PER_PAGE = 25;

    public function index(): string
    {
        $term     = $this->searchTerm();
        $products = model(ProductModel::class)->withCategoryName();

        if ($term !== '') {
            $products->search($term);
        }

        return view('admin/products/index', [
            'title'    => 'Products',
            'term'     => $term,
            'products' => $products->orderBy('products.id', 'DESC')->paginate(self::PER_PAGE),
            'pager'    => $products->pager,
        ]);
    }

    public function new(): string
    {
        return $this->form(new Product(['is_published' => true]), 'New product');
    }

    public function create(): RedirectResponse
    {
        return $this->save();
    }

    public function edit(int $id): string
    {
        $product = $this->findOrFail($id);

        return $this->form($product, "Edit {$product->name}");
    }

    public function update(int $id): RedirectResponse
    {
        return $this->save($this->findOrFail($id));
    }

    /**
     * Soft delete: past orders keep their product link and the slug stays reserved.
     */
    public function delete(int $id): RedirectResponse
    {
        $product = $this->findOrFail($id);

        model(ProductModel::class)->delete($id);

        return redirect()->route('admin.products')->with('message', "{$product->name} deleted.");
    }

    private function form(Product $product, string $title): string
    {
        return view('admin/products/form', [
            'title'      => $title,
            'product'    => $product,
            'photos'     => model(ProductModel::class)->photosOf($product),
            'categories' => model(CategoryModel::class)->ordered()->findAll(),
            'colours'    => model(ColourModel::class)->ordered()->findAll(),
            'sizes'      => model(SizeModel::class)->ordered()->findAll(),
        ]);
    }

    private function save(?Product $existing = null): RedirectResponse
    {
        $back = $existing
            ? redirect()->route('admin.products.edit', [$existing->id])
            : redirect()->route('admin.products.new');

        if (! $this->validateData($this->request->getPost(), $this->rules())) {
            return $back->withInput()->with('errors', $this->validator->getErrors());
        }

        $variants = $this->variantRows();

        if ($variants === null) {
            return $back->withInput()->with('errors', ['variant_stock' => 'Stock must be a whole number, 0 or more, up to 999,999.']);
        }

        $products = model(ProductModel::class);
        $data     = $this->formData();
        $current  = $existing ? $products->photosOf($existing) : [];
        $kept     = $this->keptPhotos($current);
        $uploads  = $this->uploadedPhotos();

        if (count($kept) + count($uploads) > ProductImages::MAX_PER_PIECE) {
            return $back->withInput()->with('errors', ['photos' => 'A piece can have up to ' . ProductImages::MAX_PER_PIECE . ' photos. Remove some to add more.']);
        }

        $added = $this->storePhotos($uploads);

        if ($added === null) {
            return $back->withInput()->with('errors', ['photos' => 'We could not read one of those photos. Please try a different file.']);
        }

        $id = $existing->id ?? $products->insert($data);

        if ($existing !== null) {
            $products->update($id, $data);
        }

        model(ProductVariantModel::class)->saveFor($id, $variants);

        $products->setPhotos($id, [...$kept, ...$added]);

        foreach (array_diff($current, $kept) as $removed) {
            service('productImages')->delete($removed);
        }

        return redirect()->route('admin.products')->with('message', $data['name'] . ($existing ? ' saved.' : ' added.'));
    }

    /**
     * A stock row for each ticked combination. Either list can be left empty, and a
     * piece with neither has no rows and uses its plain stock number.
     *
     * @return list<array{colour_id: int|null, size_id: int|null, stock: int}>|null null if a stock number is not valid
     */
    private function variantRows(): ?array
    {
        $colours = $this->ticked('colours', model(ColourModel::class));
        $sizes   = $this->ticked('sizes', model(SizeModel::class));

        if ($colours === [] && $sizes === []) {
            return [];
        }

        $stock = (array) $this->request->getPost('variant_stock');
        $rows  = [];

        foreach ($colours ?: [0] as $colour) {
            foreach ($sizes ?: [0] as $size) {
                $number = $stock[$colour][$size] ?? '';

                if (! is_string($number) || preg_match('/^\s*\d{0,6}\s*$/', $number) !== 1) {
                    return null;
                }

                $rows[] = ['colour_id' => $colour ?: null, 'size_id' => $size ?: null, 'stock' => (int) $number];
            }
        }

        return $rows;
    }

    /**
     * The IDs ticked in a list, in list order. Only IDs really on the list count, so
     * a forged form cannot add anything else.
     *
     * @return list<int>
     */
    private function ticked(string $field, OptionListModel $list): array
    {
        $posted = array_map('intval', array_filter((array) $this->request->getPost($field), 'is_scalar'));
        $known  = array_map('intval', array_column($list->ordered()->findAll(), 'id'));

        return array_values(array_intersect($known, $posted));
    }

    /**
     * What stays of a piece's photos after this save, main photo first. Only names
     * already on the piece count, so a forged form cannot reach any other file.
     *
     * @param list<string> $current
     *
     * @return list<string>
     */
    private function keptPhotos(array $current): array
    {
        $removed = array_filter((array) $this->request->getPost('remove_photos'), 'is_string');
        $kept    = array_diff($current, $removed);
        $main    = $this->request->getPost('main_photo');

        return array_values(in_array($main, $kept, true) ? array_unique([$main, ...$kept]) : $kept);
    }

    /**
     * Stores every upload or none of them.
     *
     * @param list<UploadedFile> $uploads
     *
     * @return list<string>|null the new file names, or null if one could not be read
     */
    private function storePhotos(array $uploads): ?array
    {
        $images = service('productImages');
        $stored = [];

        try {
            foreach ($uploads as $upload) {
                $stored[] = $images->store($upload);
            }
        } catch (Throwable $e) {
            // A file can pass the image checks and still be too damaged to decode.
            log_message('error', 'Product photo failed: {message}', ['message' => $e->getMessage()]);

            foreach ($stored as $name) {
                $images->delete($name);
            }

            return null;
        }

        return $stored;
    }

    private function findOrFail(int $id): Product
    {
        $product = model(ProductModel::class)->find($id);

        if ($product === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $product;
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        $rules = [
            'name' => [
                'rules'  => 'required|max_length[140]',
                'errors' => ['required' => 'Please give the product a name.', 'max_length' => 'Product names can be up to 140 characters.'],
            ],
            'category_id' => [
                'rules'  => 'permit_empty|is_natural_no_zero|is_not_unique[categories.id]',
                'errors' => ['is_natural_no_zero' => 'Please choose a category from the list.', 'is_not_unique' => 'Please choose a category from the list.'],
            ],
            'price' => [
                'rules'  => ['required', 'regex_match[/^\d{1,7}(\.\d{1,2})?$/]'],
                'errors' => ['required' => 'Please enter a price.', 'regex_match' => 'Enter the price as a number, like 349 or 349.50.'],
            ],
            'stock' => [
                'rules'  => 'required|is_natural|less_than_equal_to[999999]',
                'errors' => ['required' => 'Please enter how many you have, or 0 if sold out.', 'is_natural' => 'Stock must be a whole number, 0 or more.', 'less_than_equal_to' => 'Stock can be up to 999,999.'],
            ],
            'summary' => [
                'rules'  => 'permit_empty|max_length[200]',
                'errors' => ['max_length' => 'Keep the summary under 200 characters.'],
            ],
            'description'     => 'permit_empty|max_length[5000]',
            'is_published'    => 'permit_empty|in_list[1]',
            'is_enquiry_only' => 'permit_empty|in_list[1]',
        ];

        // Without this, a piece for sale could be ordered for nothing.
        if (! $this->request->getPost('is_enquiry_only')) {
            $rules['price']['rules'][]                = 'greater_than[0]';
            $rules['price']['errors']['greater_than'] = 'Enter a price above 0, or tick Enquiry only.';
        }

        // A piece with combinations adds its stock up from them.
        if ($this->ticked('colours', model(ColourModel::class)) !== [] || $this->ticked('sizes', model(SizeModel::class)) !== []) {
            $rules['stock']['rules'] = 'permit_empty';
        }

        if ($this->uploadedPhotos() !== []) {
            $rules['photos'] = [
                'rules'  => 'uploaded[photos]|is_image[photos]|mime_in[photos,image/jpeg,image/png,image/webp]|max_size[photos,5120]|max_dims[photos,6000,6000]',
                'errors' => [
                    'uploaded' => 'One of those photos did not upload. Please try again.',
                    'is_image' => 'Please choose only JPG, PNG or WebP photos.',
                    'mime_in'  => 'Please choose only JPG, PNG or WebP photos.',
                    'max_size' => 'Each photo can be up to 5 MB.',
                    'max_dims' => 'Each photo can be up to 6000 pixels wide or tall.',
                ],
            ];
        }

        return $rules;
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        $data = $this->validator->getValidated();

        return [
            'name'            => trim($data['name']),
            'category_id'     => ($data['category_id'] ?? '') ?: null,
            'price'           => to_minor($data['price']),
            'stock'           => (int) ($data['stock'] ?? 0),
            'summary'         => trim((string) ($data['summary'] ?? '')) ?: null,
            'description'     => trim((string) ($data['description'] ?? '')) ?: null,
            'is_published'    => empty($data['is_published']) ? 0 : 1,
            'is_enquiry_only' => empty($data['is_enquiry_only']) ? 0 : 1,
        ];
    }

    /**
     * @return list<UploadedFile>
     */
    private function uploadedPhotos(): array
    {
        $files = $this->request->getFileMultiple('photos') ?? [];

        return array_values(array_filter($files, static fn (UploadedFile $file): bool => $file->getError() !== UPLOAD_ERR_NO_FILE));
    }
}
