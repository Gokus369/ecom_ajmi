<?php

namespace Tests\Feature;

use App\Entities\Enquiry;
use App\Entities\Order;
use App\Libraries\Analytics;
use App\Models\EnquiryModel;
use App\Models\OrderModel;
use App\Models\PostStatModel;
use CodeIgniter\I18n\Time;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Services;
use Config\Store;
use Tests\Support\Traits\StoreFixtures;

final class AnalyticsTest extends CIUnitTestCase
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

    private function daysAgo(int $days): string
    {
        return Time::now('UTC')->subDays($days)->toDateTimeString();
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function makeEnquiry(int $daysAgo, array $overrides = []): int
    {
        $id = model(EnquiryModel::class)->insert([
            'type'          => Enquiry::CUSTOM,
            'customer_name' => 'Asha',
            'phone'         => '98470 11111',
            'email'         => 'asha@example.com',
            ...$overrides,
        ], true);

        $this->db->table('enquiries')->where('id', $id)->update(['created_at' => $this->daysAgo($daysAgo)]);

        return $id;
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function makeOrder(int $daysAgo, int $total, array $overrides = []): Order
    {
        $order = $this->placeOrder([[$this->makeProduct(), 1]]);

        $this->db->table('orders')->where('id', $order->id)->update([
            'created_at' => $this->daysAgo($daysAgo),
            'total'      => $total,
            ...$overrides,
        ]);

        return model(OrderModel::class)->find($order->id);
    }

    /**
     * @return array<string, mixed>
     */
    private function report(): array
    {
        return (new Analytics())->report(store_today());
    }

    public function testAnEmptyStoreReportsNothingRatherThanZeroPercent(): void
    {
        $current = $this->report()['current'];

        $this->assertSame(0, $current['enquiries']);
        $this->assertNull($current['conversion']);
        $this->assertNull($current['average_order']);
        $this->assertNull($current['repeat_share']);
    }

    public function testEnquiriesAreCountedPerWeekOverTwoWeeks(): void
    {
        foreach ([1, 3, 5, 12] as $days) {
            $this->makeEnquiry($days);
        }

        $this->makeEnquiry(15);

        $report = $this->report();

        $this->assertSame(4, $report['current']['enquiries']);
        $this->assertSame(2.0, $report['current']['enquiries_per_week']);
        $this->assertSame(1, $report['previous']['enquiries']);
    }

    public function testAnEnquiryMarkedSoldCountsAsConverted(): void
    {
        $this->makeEnquiry(2, ['order_value' => 2500000, 'phone' => '90000 00001']);
        $this->makeEnquiry(3, ['phone' => '90000 00002']);

        $current = $this->report()['current'];

        $this->assertSame(50.0, $current['conversion']);
        $this->assertSame(2500000, $current['sales']);
    }

    public function testAnEnquiryFollowedByASiteOrderFromTheSamePhoneCountsAsConverted(): void
    {
        $this->makeEnquiry(5, ['phone' => '+91 98470 12345']);
        $this->makeOrder(2, 500000, ['phone' => '9847012345']);

        $this->assertSame(100.0, $this->report()['current']['conversion']);
    }

    public function testAnOrderPlacedBeforeTheEnquiryDoesNotConvertIt(): void
    {
        $this->makeOrder(5, 500000, ['phone' => '9847012345']);
        $this->makeEnquiry(2, ['phone' => '9847012345']);

        $this->assertSame(0.0, $this->report()['current']['conversion']);
    }

    public function testAverageOrderValueCoversSiteOrdersAndLoggedSales(): void
    {
        $this->makeOrder(1, 100000);
        $this->makeEnquiry(2, ['order_value' => 300000, 'phone' => '90000 00009']);

        $current = $this->report()['current'];

        $this->assertSame(2, $current['orders']);
        $this->assertSame(200000, $current['average_order']);
    }

    public function testCancelledOrdersDoNotCount(): void
    {
        $this->makeOrder(1, 100000, ['status' => Order::CANCELLED]);

        $current = $this->report()['current'];

        $this->assertSame(0, $current['orders']);
        $this->assertSame(0, $current['sales']);
    }

    public function testRepeatCustomersIncludeOrdersFromBeforeThePeriod(): void
    {
        $this->makeOrder(40, 100000, ['phone' => '9000000001']);
        $this->makeOrder(2, 100000, ['phone' => '9000000001']);
        $this->makeOrder(3, 100000, ['phone' => '9000000002']);

        $current = $this->report()['current'];

        $this->assertSame(2, $current['customers']);
        $this->assertSame(50.0, $current['repeat_share']);
    }

    public function testReferredCustomersCount(): void
    {
        $this->makeOrder(2, 100000, ['phone' => '9000000001', 'heard_from' => Enquiry::REFERRAL]);
        $this->makeOrder(3, 100000, ['phone' => '9000000002', 'heard_from' => 'instagram']);

        $this->assertSame(50.0, $this->report()['current']['repeat_share']);
    }

    public function testChangeIsMeasuredAgainstThePreviousFortnight(): void
    {
        $this->makeOrder(2, 300000);
        $this->makeOrder(20, 200000);

        $this->assertSame(50.0, $this->report()['changes']['sales']);
    }

    public function testChangeIsUnknownWithNothingToCompare(): void
    {
        $this->makeOrder(2, 300000);

        $this->assertNull($this->report()['changes']['sales']);
    }

    public function testHistoryCoversSixFortnightsOldestFirst(): void
    {
        $this->makeOrder(2, 300000);
        $this->makeOrder(30, 200000);

        $history = $this->report()['history'];

        $this->assertCount(6, $history);
        $this->assertSame(300000, $history[5]['sales']);
        $this->assertSame(200000, $history[3]['sales']);
    }

    public function testTopPostsRankBySavesAndSharesWithinThePeriod(): void
    {
        $posts = model(PostStatModel::class);

        $posts->insert(['title' => 'Small', 'posted_on' => store_today(), 'saves' => 5, 'shares' => 5]);
        $posts->insert(['title' => 'Big', 'posted_on' => store_today(), 'saves' => 20, 'shares' => 10]);
        $posts->insert(['title' => 'Old', 'posted_on' => '2020-01-01', 'saves' => 900, 'shares' => 900]);

        $this->assertSame(['Big', 'Small'], array_map(static fn ($post): string => $post->title, $this->report()['top_posts']));
    }

    public function testAdviceNeverGivesMoreThanThreeActions(): void
    {
        // Open enquiries, falling enquiries and no posts all want attention at once.
        $this->makeEnquiry(9);
        $this->makeEnquiry(15);
        $this->makeEnquiry(16);
        $this->makeEnquiry(17);
        $this->makeOrder(20, 900000);

        $this->assertCount(3, $this->report()['actions']);
    }

    public function testAdviceNamesEnquiriesWaitingForAReply(): void
    {
        $this->makeEnquiry(4);

        $this->assertStringContainsString('1 enquiry', implode(' ', array_column($this->report()['actions'], 'title')));
    }

    public function testAdviceNoticesFewerRepeatCustomers(): void
    {
        $this->makeOrder(20, 100000, ['phone' => '9000000001', 'heard_from' => Enquiry::REFERRAL]);
        $this->makeOrder(2, 100000, ['phone' => '9000000002', 'heard_from' => Enquiry::REFERRAL]);
        $this->makeOrder(3, 100000, ['phone' => '9000000003']);

        $this->assertStringContainsString('referral', implode(' ', array_column($this->report()['actions'], 'title')));
    }

    public function testThePageIsForAdminsOnly(): void
    {
        $this->get('admin/analytics')->assertRedirectTo('login');

        $this->signInAs('user');

        $result = $this->withSession()->get('admin/analytics');

        $result->assertRedirect();
        $this->assertStringNotContainsString('admin', $result->getRedirectUrl());
    }

    public function testThePageRendersEmptyAndFull(): void
    {
        $this->signInAs('admin')->get('admin/analytics')->assertSee('Not enough data');

        $this->makeOrder(2, 300000);
        $this->makeEnquiry(3);

        $page = $this->signInAs('admin')->get('admin/analytics');

        $page->assertOK();
        $page->assertSee('₹3,000');
        $page->assertSee('Profit');
    }

    public function testADmEnquiryCanBeLogged(): void
    {
        $this->signInAs('admin')->submit('admin/analytics/enquiries', [
            'customer_name' => 'Instagram customer', 'source' => 'instagram', 'type' => Enquiry::BRIDAL, 'order_value' => '25000',
        ])->assertRedirectTo('admin/analytics');

        $this->seeInDatabase('enquiries', ['customer_name' => 'Instagram customer', 'source' => 'instagram', 'order_value' => 2500000]);
    }

    public function testABadLoggedEnquiryIsRefused(): void
    {
        $this->signInAs('admin')->submit('admin/analytics/enquiries', ['customer_name' => '', 'source' => 'nowhere', 'type' => 'x']);

        $this->assertSame(['customer_name', 'source', 'type'], array_keys(session('errors')));
        $this->seeNumRecords(0, 'enquiries', []);
    }

    public function testAnEnquiryCanBeMarkedSold(): void
    {
        $id = $this->makeEnquiry(2);

        $this->signInAs('admin')->submit("admin/enquiries/{$id}/sale", ['order_value' => '12000.50', 'source' => 'referral']);

        $this->seeInDatabase('enquiries', ['id' => $id, 'order_value' => 1200050, 'source' => 'referral']);
    }

    public function testAPostCanBeLoggedAndDeleted(): void
    {
        $this->signInAs('admin')->submit('admin/analytics/posts', [
            'title' => 'Onam reel', 'posted_on' => store_today(), 'saves' => '40', 'shares' => '12',
        ])->assertRedirectTo('admin/analytics');

        $this->seeInDatabase('post_stats', ['title' => 'Onam reel', 'saves' => 40, 'shares' => 12]);

        $id = model(PostStatModel::class)->first()->id;

        $this->signInAs('admin')->submit("admin/analytics/posts/{$id}/delete");

        $this->dontSeeInDatabase('post_stats', ['id' => $id]);
    }

    public function testAnOrderCanBeMarkedAsReferred(): void
    {
        $order = $this->makeOrder(2, 100000);

        $this->signInAs('admin')->submit("admin/orders/{$order->id}/source", ['heard_from' => Enquiry::REFERRAL]);

        $this->assertSame(Enquiry::REFERRAL, model(OrderModel::class)->find($order->id)->heard_from);
    }

    public function testAMadeUpSourceIsRefused(): void
    {
        $order = $this->makeOrder(2, 100000);

        $this->signInAs('admin')->submit("admin/orders/{$order->id}/source", ['heard_from' => 'hacker'])->assertSessionHas('error');

        $this->assertNull(model(OrderModel::class)->find($order->id)->heard_from);
    }

    public function testAWebsiteEnquiryShowsAsWebsite(): void
    {
        $id = $this->makeEnquiry(2);

        $this->signInAs('admin')->get("admin/enquiries/{$id}")->assertSee('Website');
    }

    public function testShoppersAreNotAskedAnythingNew(): void
    {
        $product = $this->makeProduct(['price' => 150000, 'stock' => 3]);

        $this->withSession(['cart' => [$product->id => 1]])->get('checkout')->assertDontSee('How did you hear');
        $this->get('bespoke')->assertDontSee('How did you hear');
    }
}
