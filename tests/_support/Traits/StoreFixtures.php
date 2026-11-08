<?php

namespace Tests\Support\Traits;

use App\Entities\Order;
use App\Entities\Product;
use App\Models\CategoryModel;
use App\Models\ColourModel;
use App\Models\OrderModel;
use App\Models\ProductModel;
use App\Models\ProductVariantModel;
use App\Models\SizeModel;
use CodeIgniter\Shield\Models\UserModel;
use CodeIgniter\Test\TestResponse;

trait StoreFixtures
{
    private bool $signedIn = false;

    protected function makeCategory(string $name = 'Kitchen'): int
    {
        return model(CategoryModel::class)->insert(['name' => $name], true);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    protected function makeProduct(array $overrides = []): Product
    {
        $id = model(ProductModel::class)->insert([
            'name'         => 'Stoneware Mug',
            'price'        => 34900,
            'stock'        => 10,
            'is_published' => 1,
            ...$overrides,
        ], true);

        return model(ProductModel::class)->find($id);
    }

    /**
     * Gives a piece its stock by colour and size, such as ['Ivory' => ['S' => 2]].
     * Use '' for the colour or size of a piece that has only one of the two.
     *
     * @param array<int|string, array<int|string, int>> $stock
     */
    protected function stockVariants(Product $product, array $stock): Product
    {
        $rows = [];

        foreach ($stock as $colour => $sizes) {
            foreach ($sizes as $size => $left) {
                $rows[] = [
                    'colour_id' => $colour === '' ? null : $this->colourId((string) $colour),
                    'size_id'   => $size === '' ? null : $this->sizeId((string) $size),
                    'stock'     => $left,
                ];
            }
        }

        model(ProductVariantModel::class)->saveFor($product->id, $rows);

        return model(ProductModel::class)->find($product->id);
    }

    /** The ID of a colour on the master list, added if it is not there yet. */
    protected function colourId(string $name): int
    {
        $colours = model(ColourModel::class);

        return $colours->where('name', $name)->first()->id ?? $colours->insert(['name' => $name, 'hex' => '#123456'], true);
    }

    /** The ID of a size on the master list, added if it is not there yet. */
    protected function sizeId(string $name): int
    {
        $sizes = model(SizeModel::class);

        return $sizes->where('name', $name)->first()->id ?? $sizes->insert(['name' => $name], true);
    }

    /** What is left of one colour and size of a piece. */
    protected function variantStock(Product $product, string $colour, string $size): int
    {
        return model(ProductModel::class)->find($product->id)->stockOf($colour, $size);
    }

    protected function stockOf(Product $product): int
    {
        return model(ProductModel::class)->withDeleted()->find($product->id)->stock;
    }

    /**
     * @param array<string, string|null> $overrides
     *
     * @return array<string, string|null>
     */
    protected function customer(array $overrides = []): array
    {
        return [
            'customer_name' => 'Asha Menon',
            'phone'         => '98470 12345',
            'email'         => 'asha@example.com',
            'address'       => '14/220 Beach Road',
            'city'          => 'Kozhikode',
            'state'         => 'Kerala',
            'postcode'      => '673001',
            'notes'         => '',
            ...$overrides,
        ];
    }

    /**
     * Places a real order through the service, skipping the checkout page.
     *
     * @param list<array{0: Product, 1: int}> $items product and quantity pairs
     */
    protected function placeOrder(array $items): Order
    {
        $lines = array_map(static fn (array $item): array => [
            'product'  => $item[0],
            'colour'   => '',
            'size'     => '',
            'quantity' => $item[1],
            'total'    => $item[0]->price * $item[1],
        ], $items);

        $subtotal = array_sum(array_column($lines, 'total'));

        return service('orders')->place($this->customer(['notes' => null]), $lines, [
            'subtotal' => $subtotal,
            'shipping' => 0,
            'total'    => $subtotal,
        ]);
    }

    protected function latestOrder(): ?Order
    {
        return model(OrderModel::class)->orderBy('id', 'DESC')->first();
    }

    /**
     * A POST with a valid CSRF token, as a real form would send.
     *
     * @param array<string, mixed> $data
     */
    protected function submit(string $uri, array $data = []): TestResponse
    {
        return $this->post($uri, [csrf_token() => csrf_hash(), ...$data]);
    }

    /**
     * Safe to call before each request in a test: Shield refuses a second
     * login, so later calls just carry the session forward.
     */
    protected function signInAs(string $group): static
    {
        if ($this->signedIn) {
            return $this->withSession();
        }

        $this->signedIn = true;

        $user = fake(UserModel::class);
        $user->addGroup($group);

        return $this->actingAs($user)->withSession();
    }
}
