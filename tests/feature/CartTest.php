<?php

namespace Tests\Feature;

use App\Models\ProductModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\Traits\StoreFixtures;

final class CartTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use StoreFixtures;

    protected $migrate   = true;
    protected $refresh   = true;
    protected $namespace = null;

    public function testEmptyCartIsDesigned(): void
    {
        $this->get('cart')->assertSee('Your cart is empty');
    }

    public function testAddingPutsTheProductInTheCart(): void
    {
        $product = $this->makeProduct();

        $this->submit('cart/add', ['product_id' => $product->id, 'quantity' => 2])->assertRedirect();

        $this->assertSame([$product->id => 2], session('cart'));
    }

    public function testAddingTwiceAddsUp(): void
    {
        $product = $this->makeProduct();

        $this->withSession(['cart' => [$product->id => 2]])
            ->submit('cart/add', ['product_id' => $product->id, 'quantity' => 3]);

        $this->assertSame([$product->id => 5], session('cart'));
    }

    public function testAddingIsCappedAtStock(): void
    {
        $product = $this->makeProduct(['stock' => 3]);

        $result = $this->submit('cart/add', ['product_id' => $product->id, 'quantity' => 9]);

        $this->assertSame([$product->id => 3], session('cart'));
        $result->assertSessionHas('warning');
    }

    public function testBadQuantitiesAreRefused(): void
    {
        $product = $this->makeProduct();

        foreach (['0', '-1', '1.5', 'abc', '100'] as $quantity) {
            $this->submit('cart/add', ['product_id' => $product->id, 'quantity' => $quantity])->assertSessionHas('error');
        }

        $this->assertEmpty(session('cart'));
    }

    public function testSoldOutAndUnknownProductsCannotBeAdded(): void
    {
        $soldOut = $this->makeProduct(['stock' => 0]);
        $hidden  = $this->makeProduct(['name' => 'Hidden', 'is_published' => 0]);

        $this->submit('cart/add', ['product_id' => $soldOut->id])->assertSessionHas('error');
        $this->submit('cart/add', ['product_id' => $hidden->id])->assertSessionHas('error');
        $this->submit('cart/add', ['product_id' => 99999])->assertSessionHas('error');

        $this->assertEmpty(session('cart'));
    }

    public function testEnquiryOnlyPiecesCannotBeAdded(): void
    {
        $product = $this->makeProduct(['stock' => 5, 'is_enquiry_only' => 1]);

        $this->submit('cart/add', ['product_id' => $product->id])->assertSessionHas('error');

        $this->assertEmpty(session('cart'));
    }

    public function testAPieceTurnedEnquiryOnlyLeavesTheCart(): void
    {
        $product = $this->makeProduct();

        model(ProductModel::class)->update($product->id, ['is_enquiry_only' => 1]);

        $result = $this->withSession(['cart' => [$product->id => 1]])->get('cart');

        $result->assertSee('no longer available');
        $this->assertEmpty(session('cart'));
    }

    public function testAForgedPostIsRejectedByCsrf(): void
    {
        $product = $this->makeProduct();

        $result = $this->post('cart/add', ['product_id' => $product->id]);

        $result->assertSessionHas('error');
        $this->assertEmpty(session('cart'));
    }

    public function testChangingAQuantity(): void
    {
        $product = $this->makeProduct();

        $this->withSession(['cart' => [$product->id => 2]])
            ->submit("cart/update/{$product->id}", ['quantity' => 4]);

        $this->assertSame([$product->id => 4], session('cart'));
    }

    public function testQuantityZeroRemovesTheLine(): void
    {
        $product = $this->makeProduct();

        $this->withSession(['cart' => [$product->id => 2]])
            ->submit("cart/update/{$product->id}", ['quantity' => 0]);

        $this->assertEmpty(session('cart'));
    }

    public function testUpdateCannotAddAProductThatIsNotInTheCart(): void
    {
        $product = $this->makeProduct();

        $this->submit("cart/update/{$product->id}", ['quantity' => 2]);

        $this->assertEmpty(session('cart'));
    }

    public function testRemovingALine(): void
    {
        $product = $this->makeProduct();

        $this->withSession(['cart' => [$product->id => 2]])->submit("cart/remove/{$product->id}");

        $this->assertEmpty(session('cart'));
    }

    public function testCartShowsCurrentPricesNotOldOnes(): void
    {
        $product = $this->makeProduct(['price' => 10000]);

        model(ProductModel::class)->update($product->id, ['price' => 20000]);

        $this->withSession(['cart' => [$product->id => 1]])->get('cart')->assertSee('₹200');
    }

    public function testCartShrinksToFitStockAndSaysSo(): void
    {
        $product = $this->makeProduct(['stock' => 2]);

        $result = $this->withSession(['cart' => [$product->id => 5]])->get('cart');

        $result->assertSee('Only 2 of Stoneware Mug left');
        $this->assertSame([$product->id => 2], session('cart'));
    }

    public function testProductsThatDisappearLeaveTheCart(): void
    {
        $soldOut = $this->makeProduct(['name' => 'Sold Out Mug', 'stock' => 0]);
        $hidden  = $this->makeProduct(['name' => 'Hidden Mug', 'is_published' => 0]);
        $fine    = $this->makeProduct(['name' => 'Fine Mug']);

        $result = $this->withSession(['cart' => [$soldOut->id => 1, $hidden->id => 1, $fine->id => 1]])->get('cart');

        $result->assertSee('Sold Out Mug just sold out');
        $result->assertSee('no longer available');
        $this->assertSame([$fine->id => 1], session('cart'));
    }

    public function testDeliveryIsChargedBelowTheThresholdOnly(): void
    {
        $cheap = $this->makeProduct(['name' => 'Cheap', 'price' => 50000]);
        $dear  = $this->makeProduct(['name' => 'Dear', 'price' => 99900]);

        $this->withSession(['cart' => [$cheap->id => 1]])->get('cart')->assertSee('₹550');
        $this->withSession(['cart' => [$dear->id => 1]])->get('cart')->assertSee('Free');
    }

    public function testBadgeCountsUnits(): void
    {
        $product = $this->makeProduct();

        $this->withSession(['cart' => [$product->id => 3]])->get('shop')->assertSee('3', 'span');
    }
}
