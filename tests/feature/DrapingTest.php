<?php

namespace Tests\Feature;

use App\Entities\Booking;
use App\Models\BookingModel;
use CodeIgniter\I18n\Time;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Services;
use Config\Store;
use Tests\Support\Traits\StoreFixtures;

final class DrapingTest extends CIUnitTestCase
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

        // Another test file can leave an admin signed in, which would hide a missing guard.
        auth()->logout();
    }

    protected function tearDown(): void
    {
        auth()->logout();

        parent::tearDown();
    }

    public function testThePageShowsTheBookingForm(): void
    {
        $page = $this->get('saree-draping');

        $page->assertOK();
        $page->assertSee('Saree draping');
        $page->assertSee('Wedding date');
        $page->assertSee('Request booking');
    }

    public function testThePageStopsPastDatesBeingPicked(): void
    {
        $body = html_entity_decode((string) $this->get('saree-draping')->getBody());

        $this->assertStringContainsString('min="' . $this->daysFromNow(0) . '"', $body);
    }

    public function testEveryStorePageLinksToTheBookingPage(): void
    {
        foreach (['/', 'shop', 'bespoke', 'cart'] as $uri) {
            $this->assertStringContainsString('href="' . url_to('draping') . '"', html_entity_decode((string) $this->get($uri)->getBody()));
        }
    }

    public function testTheHomePageOffersDraping(): void
    {
        $this->get('/')->assertSee('Book saree draping');
    }

    public function testARequestIsSavedAsNew(): void
    {
        $this->book()->assertRedirectTo('saree-draping');

        $booking = model(BookingModel::class)->first();

        $this->assertSame(Booking::PENDING, $booking->status);
        $this->assertSame('Asha Menon', $booking->customer_name);
        $this->assertSame($this->daysFromNow(30), (string) $booking->wedding_date);
        $this->assertSame('09:30', $booking->ready_by);
        $this->assertSame('Kochi', $booking->location);
        $this->assertSame(2, $booking->people);
    }

    public function testTheCustomerSeesThanksAndAWhatsAppLink(): void
    {
        $this->book();

        $page = $this->withSession()->get('saree-draping');

        $page->assertSee('Thank you');
        $page->assertSee('wa.me/916282876142');
        $page->assertDontSee('Request booking');
    }

    public function testOptionalDetailsCanBeLeftOut(): void
    {
        $this->book(['ready_by' => '', 'notes' => ''])->assertRedirectTo('saree-draping');

        $booking = model(BookingModel::class)->first();

        $this->assertNull($booking->ready_by);
        $this->assertNull($booking->notes);
    }

    public function testTodayIsAllowed(): void
    {
        $this->book(['wedding_date' => $this->daysFromNow(0)]);

        $this->assertSame(1, model(BookingModel::class)->countAllResults());
    }

    /**
     * @dataProvider badRequests
     *
     * @param array<string, string> $change
     */
    public function testABadRequestIsRefusedAndSavesNothing(array $change, string $field): void
    {
        $this->book($change)->assertRedirectTo('saree-draping');

        $this->assertArrayHasKey($field, session('errors') ?? []);
        $this->assertSame(0, model(BookingModel::class)->countAllResults());
    }

    /**
     * @return array<string, array{array<string, string>, string}>
     */
    public static function badRequests(): array
    {
        return [
            'no name'       => [['customer_name' => ''], 'customer_name'],
            'bad phone'     => [['phone' => 'call me'], 'phone'],
            'no date'       => [['wedding_date' => ''], 'wedding_date'],
            'not a date'    => [['wedding_date' => '2026-02-31'], 'wedding_date'],
            'not a time'    => [['ready_by' => '25:00'], 'ready_by'],
            'no place'      => [['location' => ''], 'location'],
            'nobody'        => [['people' => '0'], 'people'],
            'too many'      => [['people' => '21'], 'people'],
            'long notes'    => [['notes' => str_repeat('a', 501)], 'notes'],
        ];
    }

    public function testAPastDateIsRefused(): void
    {
        $this->book(['wedding_date' => $this->daysFromNow(-1)]);

        $this->assertArrayHasKey('wedding_date', session('errors') ?? []);
        $this->assertSame(0, model(BookingModel::class)->countAllResults());
    }

    public function testARequestNeedsTheSecurityToken(): void
    {
        $this->post('saree-draping', $this->form())->assertRedirect();

        $this->assertSame(0, model(BookingModel::class)->countAllResults());
    }

    public function testRequestsAreRateLimited(): void
    {
        for ($attempt = 1; $attempt <= 10; $attempt++) {
            $this->book();
        }

        $this->book()->assertStatus(429);
        $this->assertSame(10, model(BookingModel::class)->countAllResults());
    }

    public function testTheChatOffersDraping(): void
    {
        $result = $this->submit('chat', ['message' => 'I need saree draping for my wedding']);

        $reply = json_decode($result->response()->getBody(), true);

        $this->assertSame(url_to('draping'), $reply['links'][0]['url']);
    }

    public function testBookingsAreForAdminsOnly(): void
    {
        $id = $this->makeBooking();

        $this->get('admin/bookings')->assertRedirectTo('login');
        $this->get("admin/bookings/{$id}")->assertRedirectTo('login');
        $this->submit("admin/bookings/{$id}/status", ['status' => Booking::CONFIRMED])->assertRedirectTo('login');

        $this->assertSame(Booking::PENDING, model(BookingModel::class)->find($id)->status);
    }

    public function testAdminsSeeBookingsWithTheSoonestWeddingFirst(): void
    {
        $this->makeBooking(['customer_name' => 'Later Bride', 'wedding_date' => $this->daysFromNow(90)]);
        $this->makeBooking(['customer_name' => 'Sooner Bride', 'wedding_date' => $this->daysFromNow(10)]);

        $body = (string) $this->signInAs('admin')->get('admin/bookings')->getBody();

        $this->assertLessThan(strpos($body, 'Later Bride'), strpos($body, 'Sooner Bride'));
    }

    public function testAdminsCanFilterByStatus(): void
    {
        $this->makeBooking(['customer_name' => 'New Bride']);
        $this->makeBooking(['customer_name' => 'Confirmed Bride', 'status' => Booking::CONFIRMED]);

        $page = $this->signInAs('admin')->get('admin/bookings?status=confirmed');

        $page->assertSee('Confirmed Bride');
        $page->assertDontSee('New Bride');
    }

    public function testABookingShowsItsDetails(): void
    {
        $id = $this->makeBooking(['ready_by' => '14:30', 'notes' => 'Kerala style']);

        $page = $this->signInAs('admin')->get("admin/bookings/{$id}");

        $page->assertSee('Asha Menon');
        $page->assertSee('2:30 pm');
        $page->assertSee('Kerala style');
        $page->assertSee('Confirm booking');
    }

    public function testABookingMovesThroughItsSteps(): void
    {
        $id = $this->makeBooking();

        $this->signInAs('admin')->submit("admin/bookings/{$id}/status", ['status' => Booking::CONFIRMED]);
        $this->assertSame(Booking::CONFIRMED, model(BookingModel::class)->find($id)->status);

        $this->signInAs('admin')->submit("admin/bookings/{$id}/status", ['status' => Booking::COMPLETED]);
        $this->assertSame(Booking::COMPLETED, model(BookingModel::class)->find($id)->status);
    }

    public function testAStepCannotBeSkippedOrUndone(): void
    {
        $id = $this->makeBooking();

        $this->signInAs('admin')->submit("admin/bookings/{$id}/status", ['status' => Booking::COMPLETED]);
        $this->assertSame(Booking::PENDING, model(BookingModel::class)->find($id)->status);

        model(BookingModel::class)->update($id, ['status' => Booking::CANCELLED]);

        $this->signInAs('admin')->submit("admin/bookings/{$id}/status", ['status' => Booking::CONFIRMED]);
        $this->assertSame(Booking::CANCELLED, model(BookingModel::class)->find($id)->status);
    }

    public function testStoredTextIsEscapedForAdmins(): void
    {
        $id = $this->makeBooking(['customer_name' => '<script>alert(1)</script>', 'location' => '"><img src=x>', 'notes' => '<b>x</b>']);

        foreach (['admin/bookings', "admin/bookings/{$id}"] as $uri) {
            $body = (string) $this->signInAs('admin')->get($uri)->getBody();

            $this->assertStringNotContainsString('<script>alert(1)', $body);
            $this->assertStringNotContainsString('<img src=x>', $body);
            $this->assertStringNotContainsString('<b>x</b>', $body);
        }
    }

    /**
     * @param array<string, string> $overrides
     */
    private function book(array $overrides = []): \CodeIgniter\Test\TestResponse
    {
        return $this->submit('saree-draping', $this->form($overrides));
    }

    /**
     * @param array<string, string> $overrides
     *
     * @return array<string, string>
     */
    private function form(array $overrides = []): array
    {
        return [
            'customer_name' => 'Asha Menon',
            'phone'         => '98470 12345',
            'wedding_date'  => $this->daysFromNow(30),
            'ready_by'      => '09:30',
            'location'      => 'Kochi',
            'people'        => '2',
            'notes'         => '',
            ...$overrides,
        ];
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
