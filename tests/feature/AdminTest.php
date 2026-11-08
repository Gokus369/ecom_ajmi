<?php

namespace Tests\Feature;

use App\Entities\Order;
use App\Models\CategoryModel;
use App\Models\OrderModel;
use App\Models\ProductModel;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\Traits\StoreFixtures;

final class AdminTest extends CIUnitTestCase
{
    use AuthenticationTesting;
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use StoreFixtures;

    protected $migrate   = true;
    protected $refresh   = true;
    protected $namespace = null;

    public function testLoggedOutVisitorsAreSentToLogin(): void
    {
        foreach (['admin', 'admin/products', 'admin/products/new', 'admin/orders', 'admin/categories'] as $uri) {
            $this->get($uri)->assertRedirectTo('login');
        }
    }

    public function testLoggedOutPostsChangeNothing(): void
    {
        $product = $this->makeProduct();

        $this->submit("admin/products/{$product->id}/delete")->assertRedirectTo('login');

        $this->assertNotNull(model(ProductModel::class)->find($product->id));
    }

    public function testAccountsWithoutTheAdminGroupAreTurnedAway(): void
    {
        $result = $this->signInAs('user')->get('admin/orders');

        $result->assertRedirect();
        $this->assertStringNotContainsString('admin', $result->getRedirectUrl());
        $this->assertStringNotContainsString('login', $result->getRedirectUrl());
    }

    public function testDeleteCannotBeTriggeredByAGetRequest(): void
    {
        $product = $this->makeProduct();

        $this->signInAs('admin')->get("admin/products/{$product->id}/delete")->assertStatus(404);

        $this->assertNotNull(model(ProductModel::class)->find($product->id));
    }

    public function testDashboardRenders(): void
    {
        $this->makeProduct(['name' => 'Nearly Gone', 'stock' => 2]);
        $this->placeOrder([[$this->makeProduct(['name' => 'Popular', 'price' => 50000]), 2]]);

        $result = $this->signInAs('admin')->get('admin');

        $result->assertOK();
        $result->assertSee('₹1,000');
        $result->assertSee('Nearly Gone');
    }

    public function testMadeToOrderPiecesDoNotShowAsLowStock(): void
    {
        $this->makeProduct(['name' => 'Bridal Saree', 'stock' => 0, 'is_enquiry_only' => 1]);

        $this->signInAs('admin')->get('admin')->assertDontSee('Bridal Saree');
    }

    public function testAPieceCanBeMadeEnquiryOnly(): void
    {
        $this->signInAs('admin')->submit('admin/products', [
            'name'            => 'Bridal Lehenga',
            'price'           => '0',
            'stock'           => '0',
            'is_published'    => '1',
            'is_enquiry_only' => '1',
        ]);

        $this->seeInDatabase('products', ['name' => 'Bridal Lehenga', 'is_enquiry_only' => 1]);

        $this->signInAs('admin')->get('admin/products')->assertSee('On request');
    }

    public function testAPieceForSaleNeedsAPrice(): void
    {
        $this->signInAs('admin')->submit('admin/products', [
            'name'  => 'Free Saree',
            'price' => '0',
            'stock' => '4',
        ])->assertRedirectTo('admin/products/new');

        $this->assertArrayHasKey('price', session('errors'));
        $this->dontSeeInDatabase('products', ['name' => 'Free Saree']);
    }

    public function testUncheckingEnquiryOnlyMakesAPieceBuyableAgain(): void
    {
        $product = $this->makeProduct(['is_enquiry_only' => 1]);

        $this->signInAs('admin')->submit("admin/products/{$product->id}", ['name' => 'Saree', 'price' => '100', 'stock' => '1', 'is_published' => '1']);

        $this->seeInDatabase('products', ['id' => $product->id, 'is_enquiry_only' => 0]);
    }

    public function testCancelledOrdersDoNotCountAsSales(): void
    {
        $order = $this->placeOrder([[$this->makeProduct(['price' => 50000]), 2]]);

        service('orders')->moveTo($order, Order::CANCELLED);

        $this->assertSame(0, model(OrderModel::class)->revenueSince('2000-01-01 00:00:00'));
    }

    public function testAddingAProductConvertsThePrice(): void
    {
        $category = $this->makeCategory();

        $result = $this->signInAs('admin')->submit('admin/products', [
            'name'         => 'New Mug',
            'category_id'  => $category,
            'price'        => '349.50',
            'stock'        => '12',
            'is_published' => '1',
        ]);

        $result->assertRedirectTo('admin/products');
        $this->seeInDatabase('products', [
            'name'         => 'New Mug',
            'slug'         => 'new-mug',
            'price'        => 34950,
            'stock'        => 12,
            'is_published' => 1,
        ]);
    }

    public function testProductFormRejectsBadInput(): void
    {
        $result = $this->signInAs('admin')->submit('admin/products', [
            'name'        => '',
            'category_id' => '999',
            'price'       => 'free',
            'stock'       => '-1',
        ]);

        $result->assertRedirectTo('admin/products/new');
        $this->assertSame(['name', 'category_id', 'price', 'stock'], array_keys(session('errors')));
        $this->dontSeeInDatabase('products', ['price' => 0]);
    }

    public function testMassAssignmentCannotSetUnlistedColumns(): void
    {
        $product = $this->makeProduct();

        $this->signInAs('admin')->submit("admin/products/{$product->id}", [
            'name'  => 'Edited',
            'price' => '10',
            'stock' => '1',
            'slug'  => 'hijacked',
            'image' => '../../evil.php',
        ]);

        $this->seeInDatabase('products', ['id' => $product->id, 'name' => 'Edited', 'slug' => 'stoneware-mug', 'image' => null]);
    }

    public function testUncheckedBoxHidesAProduct(): void
    {
        $product = $this->makeProduct();

        $this->signInAs('admin')->submit("admin/products/{$product->id}", ['name' => 'Mug', 'price' => '10', 'stock' => '1']);

        $this->seeInDatabase('products', ['id' => $product->id, 'is_published' => 0]);
    }

    public function testDeletingAProductKeepsItsOrdersIntact(): void
    {
        $product = $this->makeProduct();
        $order   = $this->placeOrder([[$product, 1]]);

        $this->signInAs('admin')->submit("admin/products/{$product->id}/delete")->assertRedirectTo('admin/products');

        $this->assertNull(model(ProductModel::class)->find($product->id));
        $this->seeInDatabase('order_items', ['order_id' => $order->id, 'product_id' => $product->id]);
    }

    public function testEditingAMissingProductIsNotFound(): void
    {
        $this->signInAs('admin')->get('admin/products/99999/edit')->assertStatus(404);
    }

    public function testCategoriesCanBeAddedAndRenamed(): void
    {
        $this->signInAs('admin')->submit('admin/categories', ['name' => 'Garden', 'sort_order' => '3']);
        $this->seeInDatabase('categories', ['name' => 'Garden', 'slug' => 'garden', 'sort_order' => 3]);

        $id = model(CategoryModel::class)->findBySlug('garden')->id;

        $this->signInAs('admin')->submit("admin/categories/{$id}", ['name' => 'Outdoors']);
        $this->seeInDatabase('categories', ['id' => $id, 'name' => 'Outdoors', 'slug' => 'garden']);
    }

    public function testCategoryCountsLeaveOutDeletedProducts(): void
    {
        $category = $this->makeCategory();
        $this->makeProduct(['category_id' => $category]);
        $deleted = $this->makeProduct(['category_id' => $category]);

        model(ProductModel::class)->delete($deleted->id);

        $this->assertSame(1, (int) model(CategoryModel::class)->withProductCounts()[0]->product_count);
    }

    public function testCategoryNeedsAName(): void
    {
        $this->signInAs('admin')->submit('admin/categories', ['name' => ''])->assertRedirectTo('admin/categories');

        $this->assertArrayHasKey('name', session('errors'));
    }

    public function testACategoryInUseCannotBeDeleted(): void
    {
        $category = $this->makeCategory();

        $this->makeProduct(['category_id' => $category]);

        $this->signInAs('admin')->submit("admin/categories/{$category}/delete")->assertSessionHas('error');

        $this->seeInDatabase('categories', ['id' => $category]);
    }

    public function testAnEmptyCategoryCanBeDeleted(): void
    {
        $category = $this->makeCategory();

        $this->signInAs('admin')->submit("admin/categories/{$category}/delete");

        $this->dontSeeInDatabase('categories', ['id' => $category]);
    }

    public function testOrdersFilterByStatusAndSearch(): void
    {
        $new       = $this->placeOrder([[$this->makeProduct(), 1]]);
        $confirmed = $this->placeOrder([[$this->makeProduct(), 1]]);

        service('orders')->moveTo($confirmed, Order::CONFIRMED);

        $byStatus = $this->signInAs('admin')->get('admin/orders?status=confirmed');
        $byStatus->assertSee($confirmed->number);
        $byStatus->assertDontSee($new->number);

        $byNumber = $this->signInAs('admin')->get("admin/orders?q={$new->number}");
        $byNumber->assertSee($new->number);
        $byNumber->assertDontSee($confirmed->number);
    }

    public function testOrderListCountsWhatItShows(): void
    {
        $this->placeOrder([[$this->makeProduct(), 1]]);
        $this->placeOrder([[$this->makeProduct(), 1]]);

        $this->signInAs('admin')->get('admin/orders')->assertSee('2 orders');
        $this->signInAs('admin')->get('admin/orders?q=nobody')->assertSee('No orders match that filter');
    }

    public function testAdminMenuMarksTheCurrentPage(): void
    {
        $result = $this->signInAs('admin')->get('admin/orders');

        $this->assertMatchesRegularExpression('#admin/orders"\s+aria-current="page"#', $result->getBody());
        $this->assertDoesNotMatchRegularExpression('#admin/products"\s+aria-current="page"#', $result->getBody());
    }

    public function testEveryOrderStageRenders(): void
    {
        $order = $this->placeOrder([[$this->makeProduct(), 1]]);

        $this->signInAs('admin')->get("admin/orders/{$order->id}")->assertSee('Confirm order');

        foreach ([Order::CONFIRMED, Order::SHIPPED, Order::DELIVERED] as $step) {
            service('orders')->moveTo(model(OrderModel::class)->find($order->id), $step);

            $this->signInAs('admin')->get("admin/orders/{$order->id}")->assertOK();
        }

        $cancelled = $this->placeOrder([[$this->makeProduct(), 1]]);

        service('orders')->moveTo($cancelled, Order::CANCELLED);

        $this->signInAs('admin')->get("admin/orders/{$cancelled->id}")->assertDontSee('Order progress');
    }

    public function testEmptyAdminPagesRender(): void
    {
        foreach (['admin', 'admin/orders', 'admin/products', 'admin/categories', 'admin/products/new'] as $uri) {
            $this->signInAs('admin')->get($uri)->assertOK();
        }
    }

    public function testOrderMovesThroughItsSteps(): void
    {
        $order = $this->placeOrder([[$this->makeProduct(), 1]]);

        $this->signInAs('admin')->submit("admin/orders/{$order->id}/status", ['status' => Order::CONFIRMED]);

        $this->assertSame(Order::CONFIRMED, model(OrderModel::class)->find($order->id)->status);
    }

    public function testStepsCannotBeSkippedOrUndone(): void
    {
        $order = $this->placeOrder([[$this->makeProduct(), 1]]);

        $this->signInAs('admin')->submit("admin/orders/{$order->id}/status", ['status' => Order::DELIVERED])->assertSessionHas('error');
        $this->signInAs('admin')->submit("admin/orders/{$order->id}/status", ['status' => 'nonsense'])->assertSessionHas('error');

        $this->assertSame(Order::PENDING, model(OrderModel::class)->find($order->id)->status);
    }

    public function testCancellingPutsStockBack(): void
    {
        $product = $this->makeProduct(['stock' => 5]);
        $order   = $this->placeOrder([[$product, 2]]);

        $this->assertSame(3, $this->stockOf($product));

        $this->signInAs('admin')->submit("admin/orders/{$order->id}/status", ['status' => Order::CANCELLED]);

        $this->assertSame(5, $this->stockOf($product));
    }

    public function testCancellingTwiceDoesNotDoubleTheStock(): void
    {
        $product = $this->makeProduct(['stock' => 5]);
        $order   = $this->placeOrder([[$product, 2]]);

        $this->signInAs('admin')->submit("admin/orders/{$order->id}/status", ['status' => Order::CANCELLED]);
        $this->signInAs('admin')->submit("admin/orders/{$order->id}/status", ['status' => Order::CANCELLED])->assertSessionHas('error');

        $this->assertSame(5, $this->stockOf($product));
    }
}
