<?php

namespace Tests\Feature;

use App\Entities\Booking;
use App\Models\BookingModel;
use CodeIgniter\I18n\Time;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Store;
use Tests\Support\Traits\StoreFixtures;

final class AdminDashboardTest extends CIUnitTestCase
{
    use AuthenticationTesting;
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use StoreFixtures;

    protected $migrate   = true;
    protected $refresh   = true;
    protected $namespace = null;

    protected function tearDown(): void
    {
        auth()->logout();

        parent::tearDown();
    }

    public function testAQuietDayIsAllCaughtUp(): void
    {
        $this->dashboard()->assertSee('You are all caught up.');
    }

    public function testTheTopLineNamesWhatIsWaiting(): void
    {
        $this->placeOrder([[$this->makeProduct(), 1]]);
        $this->placeOrder([[$this->makeProduct(), 1]]);
        $this->makeBooking();

        $this->dashboard()->assertSee('You have 2 new orders and 1 new booking.');
    }

    public function testConfirmedBookingsAreNotCountedAsNew(): void
    {
        $this->makeBooking(['status' => Booking::CONFIRMED]);

        $this->dashboard()->assertSee('You are all caught up.');
    }

    public function testTheMenuCountsNewBookingsOnEveryAdminPage(): void
    {
        $this->makeBooking();
        $this->makeBooking();

        foreach (['admin', 'admin/products', 'admin/orders'] as $uri) {
            $this->assertStringContainsString('2<span class="sr-only"> new</span>', (string) $this->signInAs('admin')->get($uri)->getBody());
        }
    }

    public function testTheMenuShowsNoCountWhenNothingIsNew(): void
    {
        $this->assertStringNotContainsString('nav-count', (string) $this->dashboard()->getBody());
    }

    public function testUpcomingWeddingsPutTheSoonestFirst(): void
    {
        $this->makeBooking(['customer_name' => 'Later Bride', 'wedding_date' => $this->daysFromNow(60)]);
        $this->makeBooking(['customer_name' => 'Sooner Bride', 'wedding_date' => $this->daysFromNow(5)]);

        $body = (string) $this->dashboard()->getBody();

        $this->assertLessThan(strpos($body, 'Later Bride'), strpos($body, 'Sooner Bride'));
    }

    public function testUpcomingWeddingsLeaveOutPastAndClosedBookings(): void
    {
        $this->makeBooking(['customer_name' => 'Past Bride', 'wedding_date' => $this->daysFromNow(-1)]);
        $this->makeBooking(['customer_name' => 'Cancelled Bride', 'status' => Booking::CANCELLED]);
        $this->makeBooking(['customer_name' => 'Completed Bride', 'status' => Booking::COMPLETED]);
        $this->makeBooking(['customer_name' => 'Todays Bride', 'wedding_date' => $this->daysFromNow(0)]);

        $page = $this->dashboard();

        $page->assertSee('Todays Bride');
        $page->assertDontSee('Past Bride');
        $page->assertDontSee('Cancelled Bride');
        $page->assertDontSee('Completed Bride');
    }

    public function testBookingNamesAreEscaped(): void
    {
        $this->makeBooking(['customer_name' => '<script>alert(1)</script>']);

        $this->assertStringNotContainsString('<script>alert(1)', (string) $this->dashboard()->getBody());
    }

    public function testTheProductFormSeparatesPriceFromEnquiryOnly(): void
    {
        $page = $this->signInAs('admin')->get('admin/products/new');

        $page->assertSee('Price and stock');
        $page->assertSee('Enquiry only (price on request)');
    }

    private function dashboard(): \CodeIgniter\Test\TestResponse
    {
        return $this->signInAs('admin')->get('admin');
    }

    /**
     * @param array<string, int|string> $overrides
     */
    private function makeBooking(array $overrides = []): int
    {
        return model(BookingModel::class)->insert([
            'customer_name' => 'Asha Menon',
            'phone'         => '98470 12345',
            'wedding_date'  => $this->daysFromNow(30),
            'location'      => 'Kochi',
            'people'        => 2,
            ...$overrides,
        ], true);
    }

    private function daysFromNow(int $days): string
    {
        return Time::now(config(Store::class)->timezone)->addDays($days)->toDateString();
    }
}
