<?php

namespace Tests\Feature;

use App\Models\OrderModel;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\UserModel;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Services;
use Tests\Support\Traits\StoreFixtures;

final class AccountTest extends CIUnitTestCase
{
    use AuthenticationTesting;
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use StoreFixtures;

    private const PASSWORD = 'kasavu-silk-loom-27';

    protected $migrate   = true;
    protected $refresh   = true;
    protected $namespace = null;

    protected function setUp(): void
    {
        parent::setUp();

        // The rate limiter would otherwise carry its count from one test into the next.
        Services::resetSingle('throttler');
    }

    protected function tearDown(): void
    {
        // Signing up signs the customer in, and that would outlive this test.
        auth()->logout();

        parent::tearDown();
    }

    /**
     * @param array<string, string> $overrides
     *
     * @return array<string, string>
     */
    private function signUpForm(array $overrides = []): array
    {
        return [
            'name'             => 'Asha Menon',
            'email'            => 'asha@example.com',
            'password'         => self::PASSWORD,
            'password_confirm' => self::PASSWORD,
            ...$overrides,
        ];
    }

    private function userWithEmail(string $email): ?User
    {
        return auth()->getProvider()->findByCredentials(['email' => $email]);
    }

    public function testSignUpPageShowsItsForm(): void
    {
        $result = $this->get('register');

        $result->assertOK();
        $result->assertSee('Create your account');
    }

    public function testSigningUpCreatesACustomerAndSignsThemIn(): void
    {
        $this->submit('register', $this->signUpForm())->assertRedirectTo('account');

        $user = $this->userWithEmail('asha@example.com');

        $this->assertSame('Asha Menon', $user->name);
        $this->assertSame(['user'], $user->getGroups());
        $this->assertTrue(auth()->loggedIn());
    }

    public function testASignUpCannotClaimAdminPowers(): void
    {
        $this->submit('register', $this->signUpForm(['group' => 'superadmin', 'groups' => 'superadmin', 'active' => '1', 'username' => 'admin2']));

        $user = $this->userWithEmail('asha@example.com');

        $this->assertSame(['user'], $user->getGroups());
        $this->assertFalse($user->inGroup('superadmin', 'admin'));
        $this->assertNull($user->username);
    }

    public function testCustomersCannotOpenTheAdmin(): void
    {
        $this->submit('register', $this->signUpForm());

        $result = $this->withSession()->get('admin/orders');

        $result->assertRedirect();
        $this->assertStringNotContainsString('admin', $result->getRedirectUrl());
    }

    public function testAnEmailCanOnlySignUpOnce(): void
    {
        $this->submit('register', $this->signUpForm());
        auth()->logout();

        $this->withSession([])->submit('register', $this->signUpForm(['name' => 'Someone Else']))->assertRedirect();

        $this->assertArrayHasKey('email', session('errors'));
        $this->seeNumRecords(1, 'users', []);
    }

    public function testWeakOrMismatchedPasswordsAreRefused(): void
    {
        $this->submit('register', $this->signUpForm(['password' => 'password', 'password_confirm' => 'password']))->assertRedirect();
        $this->assertArrayHasKey('password', session('errors'));

        $this->withSession([])->submit('register', $this->signUpForm(['password_confirm' => 'something-else-9']))->assertRedirect();
        $this->assertArrayHasKey('password_confirm', session('errors'));

        $this->seeNumRecords(0, 'users', []);
    }

    public function testAFailedSignUpNeverPrintsThePasswordBack(): void
    {
        $this->submit('register', $this->signUpForm(['name' => '', 'email' => 'not-an-email']));

        $body = $this->withSession()->get('register')->response()->getBody();

        $this->assertStringNotContainsString(self::PASSWORD, $body);
        $this->assertStringContainsString('not-an-email', $body);
    }

    public function testCustomersSignInToTheirAccount(): void
    {
        $this->submit('register', $this->signUpForm());
        auth()->logout();

        $result = $this->withSession([])->submit('login', ['email' => 'asha@example.com', 'password' => self::PASSWORD]);

        $result->assertRedirectTo('account');
    }

    public function testAdminsStillSignInToTheAdmin(): void
    {
        $this->submit('register', $this->signUpForm(['email' => 'owner@example.com']));

        $this->userWithEmail('owner@example.com')->syncGroups('superadmin');
        auth()->logout();

        $result = $this->withSession([])->submit('login', ['email' => 'owner@example.com', 'password' => self::PASSWORD]);

        $result->assertRedirectTo('admin');
    }

    public function testAWrongPasswordIsRefused(): void
    {
        $this->submit('register', $this->signUpForm());
        auth()->logout();

        $this->withSession([])->submit('login', ['email' => 'asha@example.com', 'password' => 'not-the-password-1']);

        $this->assertFalse(auth()->loggedIn());
    }

    public function testAccountPageNeedsASignIn(): void
    {
        $this->get('account')->assertRedirectTo('login');
    }

    public function testSigningOutReturnsToTheStore(): void
    {
        $this->submit('register', $this->signUpForm());

        $result = $this->withSession()->get('logout');

        $this->assertSame(rtrim(site_url('/'), '/'), rtrim($result->getRedirectUrl(), '/'));
        $this->assertFalse(auth()->loggedIn());
    }

    public function testHeaderOffersSignInToGuestsAndOrdersToCustomers(): void
    {
        $product = $this->makeProduct();

        $this->get('shop')->assertSee('Sign in');

        $this->submit('register', $this->signUpForm());

        $result = $this->withSession()->get("products/{$product->slug}");

        $result->assertSee('My orders');
        $result->assertDontSee('Sign in');
    }

    public function testOrdersPlacedWhileSignedInJoinTheAccount(): void
    {
        $product = $this->makeProduct(['price' => 150000, 'stock' => 3]);

        $this->submit('register', $this->signUpForm());

        $user = $this->userWithEmail('asha@example.com');

        $this->withSession(['cart' => [$product->id => 1], ...$_SESSION])
            ->submit('checkout', [...$this->customer(), 'expected_total' => 150000]);

        $order = $this->latestOrder();

        $this->assertNotNull($order);
        $this->assertSame((string) $user->id, (string) $order->user_id);

        $result = $this->withSession()->get('account');

        $result->assertSee($order->number);
    }

    public function testGuestsCanStillCheckOut(): void
    {
        $product = $this->makeProduct(['price' => 150000, 'stock' => 3]);

        $this->withSession(['cart' => [$product->id => 1]])
            ->submit('checkout', [...$this->customer(), 'expected_total' => 150000]);

        $this->assertNull($this->latestOrder()->user_id);
    }

    public function testTheCheckoutFormCannotChooseTheAccount(): void
    {
        $product = $this->makeProduct(['price' => 150000, 'stock' => 3]);

        $this->withSession(['cart' => [$product->id => 1]])
            ->submit('checkout', [...$this->customer(), 'expected_total' => 150000, 'user_id' => '1']);

        $this->assertNull($this->latestOrder()->user_id);
    }

    /**
     * Email addresses are not verified, so anyone could sign up with someone
     * else's. Sharing an address must never reveal that person's orders.
     */
    public function testAnAccountNeverShowsGuestOrdersWithTheSameEmail(): void
    {
        $guestOrder = $this->placeOrder([[$this->makeProduct(), 1]]);

        $this->assertSame('asha@example.com', $guestOrder->email);

        $this->submit('register', $this->signUpForm(['email' => 'asha@example.com']));

        $this->withSession()->get('account')->assertDontSee($guestOrder->number);
    }

    public function testAnAccountNeverShowsAnotherCustomersOrders(): void
    {
        $product = $this->makeProduct();
        $theirs  = $this->placeOrder([[$product, 1]]);

        model(OrderModel::class)->update($theirs->id, ['user_id' => fake(UserModel::class)->id]);

        $this->submit('register', $this->signUpForm());

        $this->withSession()->get('account')->assertDontSee($theirs->number);
    }

    public function testCheckoutStartsWithTheCustomersDetails(): void
    {
        $product = $this->makeProduct();

        $this->submit('register', $this->signUpForm());

        $body = html_entity_decode(
            $this->withSession(['cart' => [$product->id => 1], ...$_SESSION])->get('checkout')->response()->getBody(),
        );

        $this->assertStringContainsString('value="Asha Menon"', $body);
        $this->assertStringContainsString('value="asha@example.com"', $body);
    }
}
