<?php

namespace Tests\Feature;

use App\Entities\Enquiry;
use App\Models\EnquiryModel;
use CodeIgniter\I18n\Time;
use CodeIgniter\Shield\Models\UserModel;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\TestResponse;
use Config\Services;
use Config\Store;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\Traits\StoreFixtures;

final class EnquiriesTest extends CIUnitTestCase
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

        config(Store::class)->whatsapp = '+91 62828 76142';
    }

    protected function tearDown(): void
    {
        auth()->logout();

        parent::tearDown();
    }

    public function testEachCardHasItsOwnForm(): void
    {
        $page = $this->get('bespoke');

        $page->assertOK();
        $page->assertSee('type="text" id="custom_customer_name"');
        $page->assertSee('type="text" id="bridal_customer_name"');
        $page->assertSee('Wedding date');
        $page->assertSee('type="email" id="custom_email"');
        $page->assertSee('type="email" id="bridal_email"');
        $page->assertSee('>Book</button>');
        $page->assertDontSee('open WhatsApp');
    }

    public function testThePageStopsPastDatesBeingPicked(): void
    {
        $body = html_entity_decode((string) $this->get('bespoke')->getBody());

        $this->assertStringContainsString('min="' . $this->daysFromNow(0) . '"', $body);
    }

    public function testACustomEnquiryIsSavedAsNew(): void
    {
        $this->send('custom');

        $enquiry = model(EnquiryModel::class)->first();

        $this->assertSame(Enquiry::CUSTOM, $enquiry->type);
        $this->assertSame(Enquiry::PENDING, $enquiry->status);
        $this->assertSame('Asha Menon', $enquiry->customer_name);
        $this->assertSame('98470 12345', $enquiry->phone);
        $this->assertSame('asha@example.com', $enquiry->email);
        $this->assertSame($this->daysFromNow(30), $enquiry->event_date);
        $this->assertSame('Ivory and gold for a reception', $enquiry->details);
    }

    public function testABridalEnquiryIsSavedAsBridal(): void
    {
        $this->send('bridal');

        $this->assertSame(Enquiry::BRIDAL, model(EnquiryModel::class)->first()->type);
    }

    public function testBookingStaysOnTheSiteAndSaysThankYou(): void
    {
        $result = $this->send('bridal');

        $result->assertRedirectTo('bespoke');
        $this->assertStringNotContainsString('wa.me', $result->getRedirectUrl());
        $this->assertSame('Thank you. We have your booking and will contact you soon.', $_SESSION['message']);
    }

    public function testASignedInCustomerStartsWithTheirEmailFilledIn(): void
    {
        $user = fake(UserModel::class);
        $user->createEmailIdentity(['email' => 'asha@example.com', 'password' => 'a-long-test-password']);

        $this->actingAs($user)->get('bespoke')->assertSee('value="asha@example.com"');
    }

    public function testOptionalDetailsCanBeLeftOut(): void
    {
        $this->send('custom', ['event_date' => '', 'details' => '']);

        $enquiry = model(EnquiryModel::class)->first();

        $this->assertNull($enquiry->event_date);
        $this->assertNull($enquiry->details);
    }

    public function testExtraSpacesAreTrimmed(): void
    {
        $this->send('custom', ['customer_name' => '  Asha Menon  ', 'email' => ' asha@example.com ']);

        $enquiry = model(EnquiryModel::class)->first();

        $this->assertSame('Asha Menon', $enquiry->customer_name);
        $this->assertSame('asha@example.com', $enquiry->email);
    }

    public function testLongDetailsAreKeptInFull(): void
    {
        $details = str_repeat('ഓണം', 333);

        $this->send('custom', ['details' => $details]);

        $this->assertSame($details, model(EnquiryModel::class)->first()->details);
    }

    /**
     * @param array<string, string> $change
     */
    #[DataProvider('badEnquiries')]
    public function testABadEnquiryIsRefusedAndSavesNothing(array $change, string $field): void
    {
        $this->send('custom', $change)->assertRedirectTo('bespoke');

        $this->assertSame(0, model(EnquiryModel::class)->countAllResults());
        $this->assertArrayHasKey("custom_{$field}", $_SESSION['errors']);
    }

    /**
     * @return array<string, array{0: array<string, string>, 1: string}>
     */
    public static function badEnquiries(): array
    {
        return [
            'no name'           => [['customer_name' => ''], 'customer_name'],
            'very long name'    => [['customer_name' => str_repeat('a', 101)], 'customer_name'],
            'no phone'          => [['phone' => ''], 'phone'],
            'not a phone'       => [['phone' => 'call me maybe'], 'phone'],
            'no email'          => [['email' => ''], 'email'],
            'not an email'      => [['email' => 'asha at home'], 'email'],
            'very long email'   => [['email' => str_repeat('a', 121) . '@example.com'], 'email'],
            'not a date'        => [['event_date' => 'next tuesday'], 'event_date'],
            'very long details' => [['details' => str_repeat('a', 1001)], 'details'],
        ];
    }

    public function testAPastDateIsRefused(): void
    {
        $this->send('custom', ['event_date' => $this->daysFromNow(-1)]);

        $this->assertSame(0, model(EnquiryModel::class)->countAllResults());
        $this->assertArrayHasKey('custom_event_date', $_SESSION['errors']);
    }

    public function testTodayIsAllowed(): void
    {
        $this->send('custom', ['event_date' => $this->daysFromNow(0)]);

        $this->assertSame(1, model(EnquiryModel::class)->countAllResults());
    }

    public function testAMistakeOnlyShowsOnTheFormThatMadeIt(): void
    {
        $this->send('bridal', ['phone' => '']);

        $page = $this->withSession()->get('bespoke');

        $page->assertSee('name="bridal_customer_name"');
        $page->assertSee('value="Asha Menon"');
        $this->assertSame(1, substr_count((string) $page->getBody(), 'value="Asha Menon"'));
        $this->assertSame(1, substr_count((string) $page->getBody(), 'class="field-error"'));
    }

    public function testThePageOpensOnCustomStyling(): void
    {
        $body = $this->get('bespoke')->response()->getBody();

        $this->assertMatchesRegularExpression('/id="choose-custom"[^>]* checked/', $body);
        $this->assertDoesNotMatchRegularExpression('/id="choose-bridal"[^>]* checked/', $body);
    }

    /**
     * Only one form shows at a time, so landing on the other would hide the error.
     */
    public function testAMistakeReopensTheFormThatMadeIt(): void
    {
        $this->send('bridal', ['phone' => '']);

        $body = $this->withSession()->get('bespoke')->response()->getBody();

        $this->assertMatchesRegularExpression('/id="choose-bridal"[^>]* checked/', $body);
        $this->assertDoesNotMatchRegularExpression('/id="choose-custom"[^>]* checked/', $body);
    }

    public function testAMistakeOnCustomStylingStaysOnIt(): void
    {
        $this->send('custom', ['phone' => '']);

        $body = $this->withSession()->get('bespoke')->response()->getBody();

        $this->assertMatchesRegularExpression('/id="choose-custom"[^>]* checked/', $body);
        $this->assertDoesNotMatchRegularExpression('/id="choose-bridal"[^>]* checked/', $body);
    }

    public function testAnUnknownEnquiryTypeIsNotFound(): void
    {
        $this->submit('bespoke/other', ['other_customer_name' => 'Asha'])->assertStatus(404);
    }

    public function testAnEnquiryNeedsTheSecurityToken(): void
    {
        $this->post('bespoke/custom', ['custom_customer_name' => 'Asha Menon', 'custom_phone' => '98470 12345'])->assertRedirect();

        $this->assertSame(0, model(EnquiryModel::class)->countAllResults());
    }

    public function testEnquiriesAreRateLimited(): void
    {
        for ($attempt = 1; $attempt <= 10; $attempt++) {
            $this->send('custom');
        }

        $this->send('custom')->assertStatus(429);
        $this->assertSame(10, model(EnquiryModel::class)->countAllResults());
    }

    public function testEnquiriesAreForAdminsOnly(): void
    {
        $id = $this->makeEnquiry();

        $this->get('admin/enquiries')->assertRedirectTo('login');
        $this->get("admin/enquiries/{$id}")->assertRedirectTo('login');
        $this->submit("admin/enquiries/{$id}/status", ['status' => Enquiry::CONTACTED])->assertRedirectTo('login');

        $this->assertSame(Enquiry::PENDING, model(EnquiryModel::class)->find($id)->status);
    }

    public function testCustomersCannotSeeEnquiries(): void
    {
        $id = $this->makeEnquiry();

        $result = $this->signInAs('user')->get("admin/enquiries/{$id}");

        $result->assertRedirect();
        $result->assertDontSee('Asha Menon');
    }

    public function testAdminsSeeTheNewestEnquiryFirst(): void
    {
        $older = $this->makeEnquiry(['customer_name' => 'Older Enquiry']);
        $this->makeEnquiry(['customer_name' => 'Newer Enquiry']);

        model(EnquiryModel::class)->builder()->where('id', $older)->update(['created_at' => '2026-01-01 10:00:00']);

        $body = (string) $this->signInAs('admin')->get('admin/enquiries')->getBody();

        $this->assertLessThan(strpos($body, 'Older Enquiry'), strpos($body, 'Newer Enquiry'));
    }

    public function testTheListShowsWhatWasAskedFor(): void
    {
        $this->makeEnquiry(['type' => Enquiry::BRIDAL]);

        $page = $this->signInAs('admin')->get('admin/enquiries');

        $page->assertSee('Bridal consultation');
        $page->assertSee('Asha Menon');
        $page->assertSee('98470 12345');
        $page->assertSee('asha@example.com');
    }

    public function testAdminsCanFilterByStatus(): void
    {
        $this->makeEnquiry(['customer_name' => 'New Enquiry']);
        $this->makeEnquiry(['customer_name' => 'Contacted Enquiry', 'status' => Enquiry::CONTACTED]);

        $page = $this->signInAs('admin')->get('admin/enquiries?status=contacted');

        $page->assertSee('Contacted Enquiry');
        $page->assertDontSee('New Enquiry');
    }

    public function testTheMenuLinksToEnquiries(): void
    {
        $this->signInAs('admin')->get('admin')->assertSee('admin/enquiries');
    }

    public function testAnEnquiryShowsItsDetails(): void
    {
        $id = $this->makeEnquiry(['event_date' => '2030-01-12', 'details' => 'Kerala style with gold']);

        $page = $this->signInAs('admin')->get("admin/enquiries/{$id}");

        $page->assertSee('Custom styling');
        $page->assertSee('Asha Menon');
        $page->assertSee('tel:9847012345');
        $page->assertSee('mailto:asha@example.com');
        $page->assertSee('12 Jan 2030');
        $page->assertSee('Kerala style with gold');
        $page->assertSee('Mark as contacted');
    }

    public function testAnEnquiryWithoutADateShowsNoDate(): void
    {
        $id = $this->makeEnquiry(['event_date' => null]);

        $this->signInAs('admin')->get("admin/enquiries/{$id}")->assertDontSee('1 Jan 1970');
    }

    public function testAMissingEnquiryIsNotFound(): void
    {
        $this->signInAs('admin')->get('admin/enquiries/999')->assertStatus(404);
    }

    public function testAnEnquiryMovesThroughItsSteps(): void
    {
        $id = $this->makeEnquiry();

        $this->signInAs('admin')->submit("admin/enquiries/{$id}/status", ['status' => Enquiry::CONTACTED]);
        $this->assertSame(Enquiry::CONTACTED, model(EnquiryModel::class)->find($id)->status);

        $this->signInAs('admin')->submit("admin/enquiries/{$id}/status", ['status' => Enquiry::CLOSED]);
        $this->assertSame(Enquiry::CLOSED, model(EnquiryModel::class)->find($id)->status);
    }

    public function testANewEnquiryCanBeClosedWithoutContact(): void
    {
        $id = $this->makeEnquiry();

        $this->signInAs('admin')->submit("admin/enquiries/{$id}/status", ['status' => Enquiry::CLOSED]);

        $this->assertSame(Enquiry::CLOSED, model(EnquiryModel::class)->find($id)->status);
    }

    public function testAClosedEnquiryStaysClosedAndUnknownStatusesAreRefused(): void
    {
        $id = $this->makeEnquiry(['status' => Enquiry::CLOSED]);

        $this->signInAs('admin')->submit("admin/enquiries/{$id}/status", ['status' => Enquiry::CONTACTED]);
        $this->assertSame(Enquiry::CLOSED, model(EnquiryModel::class)->find($id)->status);

        $other = $this->makeEnquiry();

        $this->signInAs('admin')->submit("admin/enquiries/{$other}/status", ['status' => 'hacked']);
        $this->assertSame(Enquiry::PENDING, model(EnquiryModel::class)->find($other)->status);
    }

    public function testTheDashboardFlagsNewEnquiries(): void
    {
        $this->makeEnquiry();
        $this->makeEnquiry();
        $this->makeEnquiry(['status' => Enquiry::CONTACTED]);

        $this->signInAs('admin')->get('admin')->assertSee('2 new enquiries');
    }

    public function testTheDashboardStaysQuietWithoutNewEnquiries(): void
    {
        $this->makeEnquiry(['status' => Enquiry::CLOSED]);

        $this->signInAs('admin')->get('admin')->assertDontSee('new enquir');
    }

    public function testStoredTextIsEscapedForAdmins(): void
    {
        $id = $this->makeEnquiry(['customer_name' => '<script>alert(1)</script>', 'email' => '"><img src=x>', 'details' => '<b>x</b>']);

        foreach (['admin/enquiries', "admin/enquiries/{$id}"] as $uri) {
            $body = (string) $this->signInAs('admin')->get($uri)->getBody();

            $this->assertStringNotContainsString('<script>alert(1)', $body);
            $this->assertStringNotContainsString('<img src=x>', $body);
            $this->assertStringNotContainsString('<b>x</b>', $body);
        }
    }

    /**
     * @param array<string, string> $overrides
     */
    private function send(string $type, array $overrides = []): TestResponse
    {
        $fields = [
            'customer_name' => 'Asha Menon',
            'phone'         => '98470 12345',
            'email'         => 'asha@example.com',
            'event_date'    => $this->daysFromNow(30),
            'details'       => 'Ivory and gold for a reception',
            ...$overrides,
        ];

        $data = [];

        foreach ($fields as $field => $value) {
            $data["{$type}_{$field}"] = $value;
        }

        return $this->submit("bespoke/{$type}", $data);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function makeEnquiry(array $overrides = []): int
    {
        return model(EnquiryModel::class)->insert([
            'type'          => Enquiry::CUSTOM,
            'customer_name' => 'Asha Menon',
            'phone'         => '98470 12345',
            'email'         => 'asha@example.com',
            ...$overrides,
        ], true);
    }

    private function daysFromNow(int $days): string
    {
        return Time::now(config(Store::class)->timezone)->addDays($days)->toDateString();
    }
}
