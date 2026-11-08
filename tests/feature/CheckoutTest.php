<?php

namespace Tests\Feature;

use App\Entities\Order;
use App\Exceptions\OutOfStock;
use App\Models\ProductModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Services;
use Tests\Support\Traits\StoreFixtures;

final class CheckoutTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use StoreFixtures;

    protected $migrate   = true;
    protected $refresh   = true;
    protected $namespace = null;

    protected function setUp(): void
    {
        parent::setUp();

        // The rate limiter would otherwise carry its count from one test into the next.
        Services::resetSingle('throttler');
    }

    public function testEmptyCartCannotCheckOut(): void
    {
        $this->get('checkout')->assertRedirectTo('cart');
        $this->submit('checkout', $this->customer())->assertRedirectTo('cart');
        $this->dontSeeInDatabase('orders', ['phone' => '98470 12345']);
    }

    public function testPlacingAnOrder(): void
    {
        $product = $this->makeProduct(['price' => 50000, 'stock' => 5]);

        $result = $this->withSession(['cart' => [$product->id => 2]])
            ->submit('checkout', [...$this->customer(), 'expected_total' => 100000]);

        $order = $this->latestOrder();

        $result->assertRedirectTo("orders/{$order->number}");
        $this->assertSame(Order::PENDING, $order->status);
        $this->assertSame('cod', $order->payment_method);
        $this->assertSame(100000, $order->subtotal);
        $this->assertSame(0, $order->shipping);
        $this->assertSame(100000, $order->total);
        $this->seeInDatabase('order_items', [
            'order_id'   => $order->id,
            'product_id' => $product->id,
            'name'       => 'Stoneware Mug',
            'unit_price' => 50000,
            'quantity'   => 2,
            'line_total' => 100000,
        ]);
        $this->assertSame(3, $this->stockOf($product));
        $this->assertEmpty(session('cart'));
    }

    public function testDeliveryFeeIsAddedToSmallOrders(): void
    {
        $product = $this->makeProduct(['price' => 34900]);

        $this->withSession(['cart' => [$product->id => 1]])
            ->submit('checkout', [...$this->customer(), 'expected_total' => 39900]);

        $order = $this->latestOrder();

        $this->assertSame(5000, $order->shipping);
        $this->assertSame(39900, $order->total);
    }

    public function testInvalidDetailsAreRejectedAndKept(): void
    {
        $product = $this->makeProduct();

        $result = $this->withSession(['cart' => [$product->id => 1]])->submit('checkout', [
            ...$this->customer(['phone' => 'call me', 'postcode' => '', 'email' => 'nope']),
            'expected_total' => 39900,
        ]);

        $result->assertRedirectTo('checkout');
        $errors = session('errors');
        $this->assertSame(['phone', 'email', 'postcode'], array_keys($errors));
        $this->assertSame('Please enter a valid phone number.', $errors['phone']);
        $this->assertSame('Asha Menon', old('customer_name'));
        $this->assertNull($this->latestOrder());
        $this->assertSame(10, $this->stockOf($product));
    }

    public function testBlankOptionalFieldsAreStoredAsNull(): void
    {
        $product = $this->makeProduct();

        $this->withSession(['cart' => [$product->id => 1]])
            ->submit('checkout', [...$this->customer(['email' => '', 'notes' => '   ']), 'expected_total' => 39900]);

        $order = $this->latestOrder();

        $this->assertNull($order->email);
        $this->assertNull($order->notes);
    }

    public function testATamperedTotalPlacesNothing(): void
    {
        $product = $this->makeProduct(['price' => 34900]);

        $result = $this->withSession(['cart' => [$product->id => 1]])
            ->submit('checkout', [...$this->customer(), 'expected_total' => 100]);

        $result->assertRedirectTo('checkout');
        $result->assertSessionHas('warning');
        $this->assertNull($this->latestOrder());
    }

    public function testAPriceChangeDuringCheckoutIsShownBeforeCharging(): void
    {
        $product = $this->makeProduct(['price' => 34900]);

        model(ProductModel::class)->update($product->id, ['price' => 44900]);

        // The shopper saw ₹399 (₹349 + ₹50 delivery) on a page loaded before the change.
        $result = $this->withSession(['cart' => [$product->id => 1]])
            ->submit('checkout', [...$this->customer(), 'expected_total' => 39900]);

        $result->assertSessionHas('warning');
        $this->assertNull($this->latestOrder());
    }

    public function testStockShrinkingDuringCheckoutIsShownBeforeCharging(): void
    {
        $product = $this->makeProduct(['price' => 100000, 'stock' => 1]);

        $result = $this->withSession(['cart' => [$product->id => 3]])
            ->submit('checkout', [...$this->customer(), 'expected_total' => 300000]);

        $result->assertSessionHas('warning');
        $this->assertNull($this->latestOrder());
    }

    public function testADoubleClickPlacesOneOrder(): void
    {
        $product = $this->makeProduct(['price' => 100000]);

        $this->withSession(['cart' => [$product->id => 1]])
            ->submit('checkout', [...$this->customer(), 'expected_total' => 100000]);

        $first = $this->latestOrder();

        $result = $this->withSession()
            ->submit('checkout', [...$this->customer(), 'expected_total' => 100000]);

        $result->assertRedirectTo("orders/{$first->number}");
        $this->assertSame($first->id, $this->latestOrder()->id);
        $this->assertSame(9, $this->stockOf($product));
    }

    public function testCheckoutIsProtectedByCsrf(): void
    {
        $product = $this->makeProduct();

        $this->withSession(['cart' => [$product->id => 1]])
            ->post('checkout', [...$this->customer(), 'expected_total' => 39900]);

        $this->assertNull($this->latestOrder());
    }

    public function testStockCannotGoNegative(): void
    {
        $product = $this->makeProduct(['stock' => 1]);

        $this->assertTrue(model(ProductModel::class)->takeStock($product->id, 1));
        $this->assertFalse(model(ProductModel::class)->takeStock($product->id, 1));
        $this->assertSame(0, $this->stockOf($product));
    }

    public function testStockIsNeverTakenFromAnEnquiryOnlyPiece(): void
    {
        $product = $this->makeProduct(['stock' => 5, 'is_enquiry_only' => 1]);

        $this->assertFalse(model(ProductModel::class)->takeStock($product->id, 1));
        $this->assertSame(5, $this->stockOf($product));
    }

    public function testOrderPageOffersWhatsAppWithTheOrderNumber(): void
    {
        $order = $this->placeOrder([[$this->makeProduct(), 1]]);

        $page = $this->get("orders/{$order->number}");

        $page->assertSee('Message us about this order');
        $page->assertSee('wa.me/916282876142');
        $page->assertSee($order->number);
    }

    public function testLosingTheRaceForTheLastItemRollsEverythingBack(): void
    {
        $plenty = $this->makeProduct(['name' => 'Plenty', 'stock' => 5]);
        $last   = $this->makeProduct(['name' => 'Last One', 'stock' => 1]);

        // Someone else buys the last one after this shopper's cart was read.
        model(ProductModel::class)->takeStock($last->id, 1);

        try {
            $this->placeOrder([[$plenty, 2], [$last, 1]]);
            $this->fail('Expected OutOfStock.');
        } catch (OutOfStock $e) {
            $this->assertSame('Last One', $e->productName);
        }

        $this->assertNull($this->latestOrder());
        $this->assertSame(5, $this->stockOf($plenty));
        $this->assertSame(0, $this->stockOf($last));
    }

    public function testOrdersKeepTheirPriceAndNameAfterAProductChanges(): void
    {
        $product = $this->makeProduct(['name' => 'Original', 'price' => 10000]);
        $order   = $this->placeOrder([[$product, 1]]);

        model(ProductModel::class)->update($product->id, ['name' => 'Renamed', 'price' => 99900]);

        $result = $this->get("orders/{$order->number}");

        $result->assertSee('Original');
        $result->assertSee('₹100');
        $result->assertDontSee('Renamed');
    }

    public function testOrderPageIsPrivateByNumber(): void
    {
        $order = $this->placeOrder([[$this->makeProduct(), 1]]);

        $this->assertMatchesRegularExpression('/^[2-9A-HJKMNP-Z]{10}$/', $order->number);

        $page = $this->get("orders/{$order->number}");

        $page->assertOK();
        $page->assertSee('noindex');
        $this->get("orders/{$order->id}")->assertStatus(404);
        $this->get('orders/NOTANORDER')->assertStatus(404);
    }

    public function testCustomerTextIsEscapedOnTheOrderPage(): void
    {
        $product = $this->makeProduct();

        $this->withSession(['cart' => [$product->id => 1]])->submit('checkout', [
            ...$this->customer(['customer_name' => '<b>Bold</b>', 'address' => '<script>alert(1)</script>']),
            'expected_total' => 39900,
        ]);

        $body = (string) $this->get('orders/' . $this->latestOrder()->number)->getBody();

        $this->assertStringNotContainsString('<script>alert(1)', $body);
        $this->assertStringNotContainsString('<b>Bold</b>', $body);
    }

    public function testCheckoutIsRateLimited(): void
    {
        $product = $this->makeProduct();

        for ($i = 0; $i < 10; $i++) {
            $this->withSession(['cart' => [$product->id => 1]])->submit('checkout', ['expected_total' => 1]);
        }

        $this->withSession(['cart' => [$product->id => 1]])->submit('checkout', ['expected_total' => 1])->assertStatus(429);
    }
}
