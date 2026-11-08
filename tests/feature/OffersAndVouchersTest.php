<?php

namespace Tests\Feature;

use App\Entities\Offer;
use App\Entities\Order;
use App\Entities\Voucher;
use App\Models\OfferModel;
use App\Models\OrderModel;
use App\Models\VoucherModel;
use CodeIgniter\I18n\Time;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Services;
use Config\Store;
use Tests\Support\Traits\StoreFixtures;

final class OffersAndVouchersTest extends CIUnitTestCase
{
    use AuthenticationTesting;
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use StoreFixtures;

    protected $migrate   = true;
    protected $refresh   = true;
    protected $namespace = null;

    protected function setUp(): void
    {
        parent::setUp();

        Services::resetSingle('throttler');

        auth()->logout();
    }

    protected function tearDown(): void
    {
        auth()->logout();

        parent::tearDown();
    }

    private function day(int $offset = 0): string
    {
        return Time::now(config(Store::class)->timezone)->addDays($offset)->toDateString();
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function makeOffer(array $overrides = []): int
    {
        return model(OfferModel::class)->insert([
            'name'           => 'Onam Offer',
            'discount_type'  => Offer::PERCENT,
            'discount_value' => 10,
            'min_order'      => 0,
            'starts_on'      => $this->day(-1),
            'ends_on'        => $this->day(1),
            'is_active'      => 1,
            ...$overrides,
        ], true);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function makeVoucher(array $overrides = []): Voucher
    {
        $id = model(VoucherModel::class)->insert([
            'code'           => 'ONAM-TEST2345',
            'batch'          => 'test',
            'name'           => 'Gift vouchers',
            'discount_type'  => Offer::AMOUNT,
            'discount_value' => 50000,
            'min_order'      => 0,
            'expires_on'     => null,
            'max_uses'       => 1,
            'is_active'      => 1,
            ...$overrides,
        ], true);

        return model(VoucherModel::class)->find($id);
    }

    /**
     * @return array<string, mixed> what the cart adds up to for a ₹10,000 piece
     */
    private function totalsForCart(int $price = 1000000): array
    {
        $cart    = service('cart');
        $product = $this->makeProduct(['price' => $price, 'stock' => 5]);

        $cart->set($product, 1);

        return $cart->totals($cart->lines());
    }

    private function checkout(int $expectedTotal, array $session): void
    {
        $this->withSession($session)->submit('checkout', [...$this->customer(), 'expected_total' => $expectedTotal]);
    }

    public function testNoOfferMeansNoDiscount(): void
    {
        $totals = $this->totalsForCart();

        $this->assertSame(0, $totals['discount']);
        $this->assertSame(1000000, $totals['total']);
    }

    public function testARunningOfferAppliesByItself(): void
    {
        $this->makeOffer();

        $totals = $this->totalsForCart();

        $this->assertSame(100000, $totals['discount']);
        $this->assertSame('Onam Offer', $totals['discount_label']);
        $this->assertSame(900000, $totals['total']);
    }

    public function testAnOfferCoversItsFirstAndLastDays(): void
    {
        $this->makeOffer(['starts_on' => $this->day(), 'ends_on' => $this->day()]);

        $this->assertSame(100000, $this->totalsForCart()['discount']);
    }

    /**
     * @dataProvider provideOffersThatDoNotApply
     */
    public function testAnOfferOutsideItsDatesOrSwitchedOffDoesNothing(int $start, int $end, int $active): void
    {
        $this->makeOffer(['starts_on' => $this->day($start), 'ends_on' => $this->day($end), 'is_active' => $active]);

        $this->assertSame(0, $this->totalsForCart()['discount']);
    }

    /**
     * @return array<string, array{int, int, int}>
     */
    public static function provideOffersThatDoNotApply(): array
    {
        return [
            'not started' => [1, 3, 1],
            'finished'    => [-3, -1, 1],
            'switched off' => [-1, 1, 0],
        ];
    }

    public function testAnOfferNeedsItsMinimumOrder(): void
    {
        $this->makeOffer(['min_order' => 2000000]);

        $this->assertSame(0, $this->totalsForCart()['discount']);
    }

    public function testTheBiggestOfferWins(): void
    {
        $this->makeOffer(['name' => 'Small', 'discount_value' => 5]);
        $this->makeOffer(['name' => 'Big', 'discount_value' => 25]);

        $this->assertSame('Big', $this->totalsForCart()['discount_label']);
    }

    public function testADiscountNeverPassesThePriceOfTheGoods(): void
    {
        $this->makeOffer(['discount_type' => Offer::AMOUNT, 'discount_value' => 5000000]);

        $totals = $this->totalsForCart();

        $this->assertSame(1000000, $totals['discount']);
        $this->assertGreaterThanOrEqual(0, $totals['total']);
    }

    public function testFreeDeliveryIsJudgedAfterTheDiscount(): void
    {
        config(Store::class)->freeShippingOver = 999;
        config(Store::class)->shippingFee      = 50;

        $this->makeOffer(['discount_value' => 50]);

        // ₹1,500 is over the line, but ₹750 after the discount is not.
        $totals = $this->totalsForCart(150000);

        $this->assertSame(5000, $totals['shipping']);
        $this->assertSame(80000, $totals['total']);
    }

    public function testTheBannerShowsWhileTheOfferRuns(): void
    {
        $this->makeOffer(['name' => 'Vishu Special']);

        $this->get('shop')->assertSee('Vishu Special');
    }

    public function testTheBannerStaysAwayOutsideTheDates(): void
    {
        $this->makeOffer(['name' => 'Vishu Special', 'starts_on' => $this->day(2), 'ends_on' => $this->day(3)]);

        $this->get('shop')->assertDontSee('Vishu Special');
    }

    public function testAnOfferNameCannotInjectMarkup(): void
    {
        $this->makeOffer(['name' => '<script>alert(1)</script>']);

        $this->assertStringNotContainsString('<script>alert(1)', $this->get('shop')->response()->getBody());
    }

    public function testAnOrderKeepsItsDiscount(): void
    {
        $this->makeOffer();

        $product = $this->makeProduct(['price' => 1000000, 'stock' => 5]);

        $this->checkout(900000, ['cart' => [$product->id => 1]]);

        $order = $this->latestOrder();

        $this->assertSame(100000, $order->discount);
        $this->assertSame('Onam Offer', $order->discount_label);
        $this->assertSame(900000, $order->total);

        // A past order is a record, so ending the offer changes nothing.
        model(OfferModel::class)->update(1, ['is_active' => 0]);

        $page = $this->get("orders/{$order->number}");

        $page->assertSee('Onam Offer');
        $page->assertSee('₹9,000');
    }

    public function testAnOrderIsRefusedIfTheOfferChangedTheTotal(): void
    {
        $product = $this->makeProduct(['price' => 1000000, 'stock' => 5]);

        $this->makeOffer();

        // The shopper saw the full price, but the offer has started since.
        $this->checkout(1000000, ['cart' => [$product->id => 1]]);

        $this->assertNull($this->latestOrder());
    }

    public function testAVoucherCanBeApplied(): void
    {
        $this->makeVoucher();

        $product = $this->makeProduct(['price' => 1000000, 'stock' => 5]);

        $this->withSession(['cart' => [$product->id => 1]])
            ->submit('cart/voucher', ['code' => ' onam-test2345 '])
            ->assertSessionHas('message');

        $totals = service('cart')->totals(service('cart')->lines());

        $this->assertSame(50000, $totals['discount']);
        $this->assertStringContainsString('ONAM-TEST2345', $totals['discount_label']);
    }

    /**
     * @dataProvider provideVouchersThatFail
     *
     * @param array<string, mixed> $overrides
     */
    public function testAVoucherThatCannotBeUsedIsRefused(array $overrides): void
    {
        $this->makeVoucher($overrides);

        $product = $this->makeProduct();

        $this->withSession(['cart' => [$product->id => 1]])
            ->submit('cart/voucher', ['code' => 'ONAM-TEST2345'])
            ->assertSessionHas('error');

        $this->assertNull(session('voucher'));
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function provideVouchersThatFail(): array
    {
        return [
            'used up'      => [['used_count' => 1]],
            'switched off' => [['is_active' => 0]],
            'expired'      => [['expires_on' => '2020-01-01']],
        ];
    }

    public function testAMadeUpCodeIsRefused(): void
    {
        $product = $this->makeProduct();

        $this->withSession(['cart' => [$product->id => 1]])
            ->submit('cart/voucher', ['code' => 'NOPE'])
            ->assertSessionHas('error');
    }

    public function testAVoucherCanLastThroughItsFinalDay(): void
    {
        $this->makeVoucher(['expires_on' => $this->day()]);

        $this->assertTrue(model(VoucherModel::class)->findUsable('ONAM-TEST2345') !== null);
    }

    public function testAVoucherCanBeRemoved(): void
    {
        $this->makeVoucher();

        $product = $this->makeProduct();

        $this->withSession(['cart' => [$product->id => 1], 'voucher' => 'ONAM-TEST2345'])->submit('cart/voucher/remove');

        $this->assertNull(session('voucher'));
    }

    public function testAVoucherBelowItsMinimumSaysHowMuchMoreIsNeeded(): void
    {
        $this->makeVoucher(['min_order' => 5000000]);

        $product = $this->makeProduct(['price' => 1000000, 'stock' => 5]);

        $page = $this->withSession(['cart' => [$product->id => 1], 'voucher' => 'ONAM-TEST2345'])->get('cart');

        $page->assertSee('Add ₹40,000 more');
        $this->assertStringContainsString('₹10,000', $page->response()->getBody());
    }

    public function testAnExpiredVoucherDropsOutOfTheCartWithANotice(): void
    {
        $this->makeVoucher(['expires_on' => '2020-01-01']);

        $product = $this->makeProduct();

        $page = $this->withSession(['cart' => [$product->id => 1], 'voucher' => 'ONAM-TEST2345'])->get('cart');

        $page->assertSee('is no longer valid');
        $this->assertNull(session('voucher'));
    }

    public function testTheBetterOfOfferAndVoucherAppliesNeverBoth(): void
    {
        $this->makeOffer();                              // ₹1,000 off ₹10,000.
        $this->makeVoucher(['discount_value' => 50000]); // ₹500 off.

        session()->set('voucher', 'ONAM-TEST2345');

        $totals = $this->totalsForCart();

        $this->assertSame(100000, $totals['discount']);
        $this->assertSame('Onam Offer', $totals['discount_label']);
        $this->assertNull($totals['voucher_id']);
    }

    public function testABetterVoucherBeatsTheOffer(): void
    {
        $this->makeOffer();
        $voucher = $this->makeVoucher(['discount_value' => 200000]);

        session()->set('voucher', 'ONAM-TEST2345');

        $totals = $this->totalsForCart();

        $this->assertSame(200000, $totals['discount']);
        $this->assertSame($voucher->id, $totals['voucher_id']);
    }

    public function testAVoucherTheShopperDidNotBenefitFromStaysUnused(): void
    {
        $this->makeOffer();

        $voucher = $this->makeVoucher();
        $product = $this->makeProduct(['price' => 1000000, 'stock' => 5]);

        $this->checkout(900000, ['cart' => [$product->id => 1], 'voucher' => 'ONAM-TEST2345']);

        $this->assertNotNull($this->latestOrder());
        $this->assertSame(0, model(VoucherModel::class)->find($voucher->id)->used_count);
    }

    public function testPlacingAnOrderSpendsTheVoucher(): void
    {
        $voucher = $this->makeVoucher();
        $product = $this->makeProduct(['price' => 1000000, 'stock' => 5]);

        $this->checkout(950000, ['cart' => [$product->id => 1], 'voucher' => 'ONAM-TEST2345']);

        $order = $this->latestOrder();

        $this->assertSame(950000, $order->total);
        $this->assertSame($voucher->id, (int) $order->voucher_id);
        $this->assertSame(1, model(VoucherModel::class)->find($voucher->id)->used_count);
        $this->assertNull(session('voucher'));
    }

    public function testASingleUseVoucherCannotBeSpentTwice(): void
    {
        $voucher = $this->makeVoucher();
        $product = $this->makeProduct(['price' => 1000000, 'stock' => 5]);
        $totals  = ['subtotal' => 1000000, 'discount' => 50000, 'discount_label' => 'Voucher', 'voucher_id' => $voucher->id, 'shipping' => 0, 'total' => 950000];
        $line    = [['product' => $product, 'colour' => '', 'size' => '', 'quantity' => 1, 'total' => 1000000]];

        service('orders')->place($this->customer(['notes' => null]), $line, $totals);

        try {
            service('orders')->place($this->customer(['notes' => null]), $line, $totals);
            $this->fail('The second order should have been refused.');
        } catch (\App\Exceptions\VoucherUnavailable) {
            // Expected.
        }

        $this->assertSame(1, model(OrderModel::class)->countAllResults());
        $this->assertSame(4, $this->stockOf($product));
    }

    public function testLosingTheRaceForAVoucherExplainsItself(): void
    {
        $voucher = $this->makeVoucher();
        $product = $this->makeProduct(['price' => 1000000, 'stock' => 5]);

        // Spent after the page checked it, just before this order reached the database.
        $this->assertTrue(model(VoucherModel::class)->redeem($voucher->id));

        $orders = new class (model(OrderModel::class), model(\App\Models\OrderItemModel::class), model(\App\Models\ProductModel::class), model(VoucherModel::class)) extends \App\Libraries\OrderService {};

        $this->expectException(\App\Exceptions\VoucherUnavailable::class);

        $orders->place($this->customer(['notes' => null]), [['product' => $product, 'colour' => '', 'size' => '', 'quantity' => 1, 'total' => 1000000]], [
            'subtotal' => 1000000, 'discount' => 50000, 'discount_label' => 'Voucher', 'voucher_id' => $voucher->id, 'shipping' => 0, 'total' => 950000,
        ]);
    }

    public function testCancellingGivesTheVoucherBack(): void
    {
        $voucher = $this->makeVoucher();
        $product = $this->makeProduct(['price' => 1000000, 'stock' => 5]);

        $this->checkout(950000, ['cart' => [$product->id => 1], 'voucher' => 'ONAM-TEST2345']);

        service('orders')->moveTo($this->latestOrder(), Order::CANCELLED);

        $this->assertSame(0, model(VoucherModel::class)->find($voucher->id)->used_count);
    }

    public function testVoucherGuessingIsRateLimited(): void
    {
        $product = $this->makeProduct();
        $session = ['cart' => [$product->id => 1]];

        for ($i = 0; $i < 10; $i++) {
            $this->withSession($session)->submit('cart/voucher', ['code' => "GUESS{$i}"]);
        }

        $this->withSession($session)->submit('cart/voucher', ['code' => 'GUESS11'])->assertStatus(429);
    }

    public function testOffersAndVouchersAreForAdminsOnly(): void
    {
        foreach (['admin/offers', 'admin/offers/new', 'admin/vouchers', 'admin/vouchers/export'] as $uri) {
            $this->get($uri)->assertRedirectTo('login');
        }

        $this->signInAs('user');

        foreach (['admin/offers', 'admin/vouchers'] as $uri) {
            $result = $this->withSession()->get($uri);

            $result->assertRedirect();
            $this->assertStringNotContainsString('admin', $result->getRedirectUrl());
        }
    }

    public function testAnAdminCanCreateAnOffer(): void
    {
        $this->signInAs('admin')->submit('admin/offers', [
            'name'           => 'Vishu Offer',
            'discount_type'  => Offer::AMOUNT,
            'discount_value' => '500.50',
            'min_order'      => '2000',
            'starts_on'      => $this->day(),
            'ends_on'        => $this->day(5),
            'is_active'      => '1',
        ])->assertRedirectTo('admin/offers');

        $this->seeInDatabase('offers', ['name' => 'Vishu Offer', 'discount_value' => 50050, 'min_order' => 200000, 'is_active' => 1]);
    }

    public function testAPercentOfferKeepsItsWholeNumber(): void
    {
        $this->signInAs('admin')->submit('admin/offers', [
            'name' => 'Onam', 'discount_type' => Offer::PERCENT, 'discount_value' => '20',
            'starts_on' => $this->day(), 'ends_on' => $this->day(), 'is_active' => '1',
        ]);

        $this->seeInDatabase('offers', ['name' => 'Onam', 'discount_value' => 20, 'min_order' => 0]);
    }

    /**
     * @dataProvider provideBadOffers
     *
     * @param array<string, string> $change
     */
    public function testABadOfferIsRefused(array $change, string $field): void
    {
        $this->signInAs('admin')->submit('admin/offers', [
            'name' => 'Onam', 'discount_type' => Offer::PERCENT, 'discount_value' => '20',
            'starts_on' => '2030-01-10', 'ends_on' => '2030-01-20', ...$change,
        ])->assertRedirect();

        $this->assertArrayHasKey($field, session('errors'));
        $this->seeNumRecords(0, 'offers', []);
    }

    /**
     * @return array<string, array{array<string, string>, string}>
     */
    public static function provideBadOffers(): array
    {
        return [
            'no name'         => [['name' => ''], 'name'],
            'percent too big' => [['discount_value' => '95'], 'discount_value'],
            'percent zero'    => [['discount_value' => '0'], 'discount_value'],
            'percent decimal' => [['discount_value' => '12.5'], 'discount_value'],
            'bad type'        => [['discount_type' => 'free'], 'discount_type'],
            'ends too soon'   => [['ends_on' => '2030-01-01'], 'ends_on'],
            'bad date'        => [['starts_on' => 'soon'], 'starts_on'],
        ];
    }

    public function testAnOfferCanBeEditedAndDeleted(): void
    {
        $id = $this->makeOffer();

        $this->signInAs('admin')->submit("admin/offers/{$id}", [
            'name' => 'Renamed', 'discount_type' => Offer::PERCENT, 'discount_value' => '15',
            'starts_on' => $this->day(), 'ends_on' => $this->day(2),
        ])->assertRedirectTo('admin/offers');

        $this->seeInDatabase('offers', ['id' => $id, 'name' => 'Renamed', 'discount_value' => 15, 'is_active' => 0]);

        $this->signInAs('admin')->submit("admin/offers/{$id}/delete");

        $this->dontSeeInDatabase('offers', ['id' => $id]);
    }

    public function testTheOfferListSaysWhatIsRunning(): void
    {
        $this->makeOffer(['name' => 'Now']);
        $this->makeOffer(['name' => 'Later', 'starts_on' => $this->day(2), 'ends_on' => $this->day(3)]);

        $page = $this->signInAs('admin')->get('admin/offers');

        $page->assertSee('Running');
        $page->assertSee('Scheduled');
    }

    public function testGeneratingMakesUniqueCodes(): void
    {
        $this->signInAs('admin')->submit('admin/vouchers', [
            'name' => 'Onam gifts', 'prefix' => 'onam', 'quantity' => '25',
            'discount_type' => Offer::PERCENT, 'discount_value' => '15', 'max_uses' => '1',
        ])->assertRedirect();

        $vouchers = model(VoucherModel::class)->findAll();
        $codes    = array_column(array_map(static fn (Voucher $voucher): array => $voucher->toArray(), $vouchers), 'code');

        $this->assertCount(25, $vouchers);
        $this->assertCount(25, array_unique($codes));
        $this->assertCount(1, array_unique(array_column(array_map(static fn (Voucher $voucher): array => $voucher->toArray(), $vouchers), 'batch')));

        foreach ($codes as $code) {
            $this->assertMatchesRegularExpression('/^ONAM-[2-9A-HJKMNP-Z]{8}$/', $code);
        }
    }

    /**
     * @dataProvider provideBadBatches
     *
     * @param array<string, string> $change
     */
    public function testABadBatchIsRefused(array $change, string $field): void
    {
        $this->signInAs('admin')->submit('admin/vouchers', [
            'name' => 'Gifts', 'quantity' => '5', 'discount_type' => Offer::PERCENT, 'discount_value' => '15', 'max_uses' => '1', ...$change,
        ])->assertRedirect();

        $this->assertArrayHasKey($field, session('errors'));
        $this->seeNumRecords(0, 'vouchers', []);
    }

    /**
     * @return array<string, array{array<string, string>, string}>
     */
    public static function provideBadBatches(): array
    {
        return [
            'too many'   => [['quantity' => '501'], 'quantity'],
            'none'       => [['quantity' => '0'], 'quantity'],
            'bad prefix' => [['prefix' => 'a b!'], 'prefix'],
            'past date'  => [['expires_on' => '2020-01-01'], 'expires_on'],
            'no uses'    => [['max_uses' => '0'], 'max_uses'],
        ];
    }

    public function testVouchersDownloadAsAFile(): void
    {
        $this->makeVoucher();

        $result = $this->signInAs('admin')->get('admin/vouchers/export?batch=test');

        $this->assertStringContainsString('text/csv', $result->response()->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('attachment', $result->response()->getHeaderLine('Content-Disposition'));
        $this->assertStringContainsString('ONAM-TEST2345', $result->response()->getBody());
    }

    public function testADownloadCannotCarryASpreadsheetFormula(): void
    {
        $this->makeVoucher(['name' => '=HYPERLINK("http://evil")']);

        $body = $this->signInAs('admin')->get('admin/vouchers/export')->response()->getBody();

        // A leading apostrophe makes a spreadsheet treat the cell as plain text.
        $this->assertStringContainsString("'=HYPERLINK", $body);
    }

    public function testAVoucherCanBeSwitchedOff(): void
    {
        $voucher = $this->makeVoucher();

        $this->signInAs('admin')->submit("admin/vouchers/{$voucher->id}/toggle");

        $this->assertNull(model(VoucherModel::class)->findUsable('ONAM-TEST2345'));
    }

    public function testTheMenuLinksToBoth(): void
    {
        $page = $this->signInAs('admin')->get('admin');

        $page->assertSee('Festival offers');
        $page->assertSee('Vouchers');
    }

    public function testAdminOrdersShowTheDiscount(): void
    {
        $product = $this->makeProduct(['price' => 1000000, 'stock' => 5]);

        $this->makeOffer();
        $this->checkout(900000, ['cart' => [$product->id => 1]]);

        $this->signInAs('admin')->get('admin/orders/' . $this->latestOrder()->id)->assertSee('Onam Offer');
    }
}
