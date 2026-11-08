<?php

namespace Tests\Feature;

use App\Libraries\GoogleClient;
use CodeIgniter\HTTP\Response;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\HTTP\URI;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\UserIdentityModel;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\Mock\MockCURLRequest;
use CodeIgniter\Test\TestResponse;
use Config\App;
use Config\Google;
use Config\Services;
use Tests\Support\Traits\StoreFixtures;

final class GoogleSignInTest extends CIUnitTestCase
{
    use AuthenticationTesting;
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use StoreFixtures;

    private const PASSWORD = 'kasavu-silk-loom-27';

    private const PROFILE = [
        'sub'            => '1098765432101234567',
        'email'          => 'asha@gmail.com',
        'email_verified' => true,
        'name'           => 'Asha Menon',
    ];

    protected $migrate   = true;
    protected $refresh   = true;
    protected $namespace = null;

    protected function setUp(): void
    {
        parent::setUp();

        // The rate limiter would otherwise carry its count from one test into the next.
        Services::resetSingle('throttler');

        $this->connectGoogle();
    }

    protected function tearDown(): void
    {
        // Signing in would otherwise outlive this test.
        auth()->logout();

        parent::tearDown();
    }

    private function connectGoogle(): void
    {
        $config = config(Google::class);

        $config->clientId     = 'test-client-id';
        $config->clientSecret = 'test-client-secret';
    }

    /**
     * Stands in for Google, which a test must never really call.
     *
     * @param array<string, mixed>|null $profile
     */
    private function googleReplies(?array $profile): GoogleClient
    {
        $client = new class ($profile) extends GoogleClient {
            public int $calls = 0;

            /** @param array<string, mixed>|null $reply */
            public function __construct(private readonly ?array $reply)
            {
            }

            public function profile(string $code, string $verifier): ?array
            {
                $this->calls++;

                return $this->reply;
            }
        };

        Services::injectMock('google', $client);

        return $client;
    }

    /**
     * Goes out to Google and comes back, as a browser would.
     *
     * @param array<string, mixed>|null $profile
     */
    private function signInWithGoogle(?array $profile = self::PROFILE): TestResponse
    {
        $this->googleReplies($profile);
        $this->get('auth/google');

        $state = session('google_sign_in')['state'];

        return $this->withSession()->get("auth/google/callback?state={$state}&code=from-google");
    }

    private function makePasswordAccount(string $email, string $group = 'user'): User
    {
        $users = auth()->getProvider();
        $user  = new User(['email' => $email, 'password' => self::PASSWORD, 'name' => 'Existing Customer']);

        $users->save($user);

        $user = $users->findById($users->getInsertID());
        $user->activate();
        $user->syncGroups($group);

        return $user;
    }

    private function googleAccount(): ?User
    {
        $identity = model(UserIdentityModel::class)->getIdentityBySecret('google', self::PROFILE['sub']);

        return $identity === null ? null : auth()->getProvider()->findById($identity->user_id);
    }

    public function testTheButtonStaysHiddenUntilGoogleIsConnected(): void
    {
        config(Google::class)->clientId = '';

        foreach (['login', 'register'] as $uri) {
            $this->get($uri)->assertDontSee('Continue with Google');
        }

        $this->get('auth/google')->assertStatus(404);
        $this->get('auth/google/callback?state=x&code=y')->assertStatus(404);
    }

    public function testTheButtonShowsOnBothPagesOnceConnected(): void
    {
        foreach (['login', 'register'] as $uri) {
            $this->get($uri)->assertSee('Continue with Google');
        }
    }

    public function testStartingSendsTheShopperToGoogleWithProofOfWho(): void
    {
        $this->googleReplies(self::PROFILE);

        $location = $this->get('auth/google')->getRedirectUrl();
        $pending  = session('google_sign_in');

        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

        $this->assertStringStartsWith('https://accounts.google.com/', $location);
        $this->assertSame('test-client-id', $query['client_id']);
        $this->assertSame('code', $query['response_type']);
        $this->assertStringEndsWith('auth/google/callback', $query['redirect_uri']);
        $this->assertSame($pending['state'], $query['state']);
        $this->assertSame('S256', $query['code_challenge_method']);
        $this->assertNotSame($pending['verifier'], $query['code_challenge']);
        $this->assertArrayNotHasKey('client_secret', $query);
    }

    public function testANewGoogleUserBecomesACustomer(): void
    {
        $this->signInWithGoogle()->assertRedirectTo('account');

        $user = $this->googleAccount();

        $this->assertNotNull($user);
        $this->assertSame('Asha Menon', $user->name);
        $this->assertSame('asha@gmail.com', $user->email);
        $this->assertSame(['user'], $user->getGroups());
        $this->assertTrue(auth()->loggedIn());
    }

    public function testAReturningGoogleUserGetsTheSameAccount(): void
    {
        $this->signInWithGoogle();

        $firstId = auth()->id();
        auth()->logout();

        // Google's ID never changes, even when someone changes their address.
        $this->withSession([])->signInWithGoogle([...self::PROFILE, 'email' => 'asha.new@gmail.com'])->assertRedirectTo('account');

        $this->assertSame($firstId, auth()->id());
        $this->seeNumRecords(1, 'users', []);
    }

    public function testAGoogleAccountCannotBeOpenedWithAPassword(): void
    {
        $this->signInWithGoogle();
        auth()->logout();

        foreach (['', self::PASSWORD, 'password'] as $guess) {
            $this->withSession([])->submit('login', ['email' => 'asha@gmail.com', 'password' => $guess]);

            $this->assertFalse(auth()->loggedIn());
        }
    }

    /**
     * Emails are not verified here, so an attacker could sign up with someone's
     * Gmail address first. Merging would keep the attacker's password working.
     */
    public function testGoogleNeverMergesIntoAnExistingPasswordAccount(): void
    {
        $existing = $this->makePasswordAccount('asha@gmail.com');

        $this->signInWithGoogle()->assertRedirectTo('login');

        $this->assertFalse(auth()->loggedIn());
        $this->assertSessionHasError();
        $this->assertNull($this->googleAccount());
        $this->seeNumRecords(1, 'users', []);
        $this->assertSame(['user'], auth()->getProvider()->findById($existing->id)->getGroups());
    }

    public function testAnExistingAddressIsMatchedWhateverItsCapitals(): void
    {
        $this->makePasswordAccount('asha@gmail.com');

        $this->signInWithGoogle([...self::PROFILE, 'email' => 'Asha@Gmail.com']);

        $this->assertFalse(auth()->loggedIn());
        $this->seeNumRecords(1, 'users', []);
    }

    public function testGoogleCanNeverOpenTheAdmin(): void
    {
        $this->makePasswordAccount('owner@gmail.com', 'superadmin');

        $this->signInWithGoogle([...self::PROFILE, 'email' => 'owner@gmail.com'])->assertRedirectTo('login');

        $this->assertFalse(auth()->loggedIn());
    }

    public function testAnAddressGoogleHasNotVerifiedIsRefused(): void
    {
        foreach ([false, 'true', null] as $verified) {
            $this->withSession([])->signInWithGoogle([...self::PROFILE, 'email_verified' => $verified])->assertRedirectTo('login');

            $this->assertFalse(auth()->loggedIn());
        }

        $this->seeNumRecords(0, 'users', []);
    }

    public function testAnIncompleteProfileIsRefused(): void
    {
        foreach (['sub', 'email'] as $missing) {
            $profile = self::PROFILE;

            unset($profile[$missing]);

            $this->withSession([])->signInWithGoogle($profile)->assertRedirectTo('login');
        }

        $this->seeNumRecords(0, 'users', []);
    }

    public function testAForgedReturnIsRefusedBeforeAskingGoogle(): void
    {
        $client = $this->googleReplies(self::PROFILE);

        $this->get('auth/google');

        $this->withSession()->get('auth/google/callback?state=forged&code=stolen')->assertRedirectTo('login');

        $this->assertSame(0, $client->calls);
        $this->assertFalse(auth()->loggedIn());
    }

    public function testAReturnWithNoSignInUnderwayIsRefused(): void
    {
        $client = $this->googleReplies(self::PROFILE);

        $this->get('auth/google/callback?state=anything&code=stolen')->assertRedirectTo('login');

        $this->assertSame(0, $client->calls);
    }

    public function testAReturnLinkOnlyWorksOnce(): void
    {
        // A failed first try leaves the session in place, so only the one-use rule stops the second.
        $client = $this->googleReplies(null);

        $this->get('auth/google');

        $url = 'auth/google/callback?state=' . session('google_sign_in')['state'] . '&code=from-google';

        $this->withSession()->get($url);
        $this->withSession()->get($url)->assertRedirectTo('login');

        $this->assertSame(1, $client->calls);
    }

    public function testCancellingAtGoogleReturnsToSignIn(): void
    {
        $this->googleReplies(self::PROFILE);
        $this->get('auth/google');

        $this->withSession()->get('auth/google/callback?error=access_denied')->assertRedirectTo('login');

        $this->assertSame('Google sign-in was cancelled.', session('error'));
        $this->assertFalse(auth()->loggedIn());
    }

    public function testAProblemAtGoogleIsExplainedGently(): void
    {
        $this->signInWithGoogle(null)->assertRedirectTo('login');

        $this->assertSessionHasError();
        $this->seeNumRecords(0, 'users', []);
    }

    public function testABannedCustomerStaysOut(): void
    {
        $this->signInWithGoogle();
        auth()->logout();

        $this->googleAccount()->ban();

        $this->withSession([])->signInWithGoogle()->assertRedirectTo('login');

        $this->assertFalse(auth()->loggedIn());
    }

    public function testAnAddressUsedWithGoogleCannotSignUpWithAPassword(): void
    {
        $this->signInWithGoogle();
        auth()->logout();

        $this->withSession([])->submit('register', [
            'name'             => 'Someone Else',
            'email'            => 'asha@gmail.com',
            'password'         => self::PASSWORD,
            'password_confirm' => self::PASSWORD,
        ]);

        $this->assertArrayHasKey('email', session('errors'));
        $this->seeNumRecords(1, 'users', []);
    }

    public function testACustomerAlreadySignedInGoesToTheirAccount(): void
    {
        $this->signInWithGoogle();

        $this->withSession()->get('auth/google')->assertRedirectTo('account');
    }

    public function testTheClientSendsTheProofAndReadsTheProfile(): void
    {
        $curl = $this->mockGoogleServer((string) json_encode(['access_token' => 'token-1', ...self::PROFILE]));

        $profile = (new GoogleClient())->profile('from-google', 'the-verifier');

        $this->assertSame('asha@gmail.com', $profile['email']);

        [$exchange, $reading] = $curl->sent;

        $this->assertSame('POST', $exchange['method']);
        $this->assertSame('from-google', $exchange['options']['form_params']['code']);
        $this->assertSame('the-verifier', $exchange['options']['form_params']['code_verifier']);
        $this->assertSame('test-client-secret', $exchange['options']['form_params']['client_secret']);
        $this->assertSame('GET', $reading['method']);
        $this->assertSame('Bearer token-1', $reading['options']['headers']['Authorization']);
    }

    public function testTheClientReturnsNothingWhenGoogleRefuses(): void
    {
        $curl = $this->mockGoogleServer((string) json_encode(['error' => 'invalid_grant']), 400);

        $this->assertNull((new GoogleClient())->profile('expired', 'the-verifier'));
        $this->assertCount(1, $curl->sent);
    }

    private function mockGoogleServer(string $body, int $status = 200): MockCURLRequest
    {
        $response = new Response(new App());

        $curl = new class (new App(), new URI('https://oauth2.googleapis.com/'), $response) extends MockCURLRequest {
            /** @var list<array{method: string, options: array<string, mixed>}> */
            public array $sent = [];

            public function request($method, string $url, array $options = []): ResponseInterface
            {
                $this->sent[] = ['method' => $method, 'options' => $options];

                return parent::request($method, $url, $options);
            }
        };

        $curl->setOutput("HTTP/1.1 {$status} OK\r\nContent-Type: application/json\r\n\r\n{$body}");

        Services::injectMock('curlrequest', $curl);

        return $curl;
    }

    private function assertSessionHasError(): void
    {
        $this->assertIsString(session('error'));
        $this->assertNotSame('', session('error'));
    }
}
