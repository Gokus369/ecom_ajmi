<?php

namespace Tests\Feature;

use App\Libraries\EmailCodeActivator;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Auth;
use Config\Email;
use Config\Services;
use Tests\Support\Traits\StoreFixtures;

final class EmailCodeTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use StoreFixtures;

    private const EMAIL    = 'asha@example.com';
    private const PASSWORD = 'kasavu-silk-loom-27';

    protected $migrate   = true;
    protected $refresh   = true;
    protected $namespace = null;

    protected function setUp(): void
    {
        parent::setUp();

        Services::resetSingle('throttler');

        config(Auth::class)->actions['register'] = EmailCodeActivator::class;

        $email            = config(Email::class);
        $email->fromEmail = 'hello@example.com';
        $email->fromName  = 'RUA by Ajmi Anzari';
    }

    protected function tearDown(): void
    {
        auth()->logout();

        parent::tearDown();
    }

    public function testNoMailServerMeansNoCodeStep(): void
    {
        config(Email::class)->SMTPHost = '';

        $this->assertNull((new Auth())->actions['register']);
    }

    public function testAMailServerTurnsTheCodeStepOn(): void
    {
        config(Email::class)->SMTPHost = 'smtp.example.com';

        $this->assertSame(EmailCodeActivator::class, (new Auth())->actions['register']);
    }

    public function testSigningUpAsksForTheEmailedCode(): void
    {
        $this->signUp()->assertRedirectTo('auth/a/show');

        $page = $this->withSession()->get('auth/a/show');

        $page->assertOK();
        $page->assertSee('Verify your email');
        $page->assertSee(self::EMAIL);

        $mail = service('email')->archive;

        $this->assertStringContainsString(self::EMAIL, $mail['recipients'][0]);
        $this->assertStringContainsString('RUA', $mail['body']);
        $this->assertNotSame('', $this->sentCode());
        $this->assertFalse(auth()->loggedIn());
    }

    public function testTheRightCodeFinishesSigningUp(): void
    {
        $this->signUp();
        $this->withSession()->get('auth/a/show');

        $result = $this->withSession()->submit('auth/a/verify', ['token' => $this->sentCode()]);

        $result->assertRedirectTo('account');
        $this->assertTrue($this->customer()->active);
        $this->assertTrue(auth()->loggedIn());
    }

    public function testAWrongCodeIsRefused(): void
    {
        $this->signUp();
        $this->withSession()->get('auth/a/show');

        $code   = $this->sentCode() === '111111' ? '222222' : '111111';
        $result = $this->withSession()->submit('auth/a/verify', ['token' => $code]);

        $result->assertOK();
        $result->assertSee('Verify your email');
        $this->assertFalse($this->customer()->active);
        $this->assertFalse(auth()->loggedIn());
    }

    public function testAnUnverifiedCustomerCannotOpenTheirAccount(): void
    {
        $this->signUp();

        $this->withSession()->get('account')->assertRedirectTo('auth/a/show');
    }

    public function testSigningInBeforeVerifyingAsksForTheCode(): void
    {
        $this->signUp();
        $this->withSession()->get('auth/a/show');
        auth()->logout();

        $result = $this->withSession([])->submit('login', ['email' => self::EMAIL, 'password' => self::PASSWORD]);

        $result->assertRedirectTo('auth/a/show');
        $this->assertFalse(auth()->loggedIn());
    }

    public function testAnOldSignInPageCannotBreakAWaitingCode(): void
    {
        $this->signUp();

        $result = $this->withSession()->submit('login', ['email' => self::EMAIL, 'password' => self::PASSWORD]);

        $result->assertRedirectTo('auth/a/show');
    }

    public function testFixingAMistypedAddressCannotBreakAWaitingCode(): void
    {
        $this->signUp();

        $result = $this->withSession()->submit('register', [
            'name'             => 'Asha Menon',
            'email'            => 'asha.menon@example.com',
            'password'         => self::PASSWORD,
            'password_confirm' => self::PASSWORD,
        ]);

        $result->assertRedirectTo('auth/a/show');
        $this->assertNull(auth()->getProvider()->findByCredentials(['email' => 'asha.menon@example.com']));
    }

    public function testACustomerCanLeaveTheCodePageToUseAnotherAddress(): void
    {
        $this->signUp();

        $page = $this->withSession()->get('auth/a/show');

        $page->assertSee('Use a different email');

        $this->withSession()->get('logout');

        $this->withSession([])->get('register')->assertOK();
    }

    public function testAnOldSignInPageSendsASignedInCustomerOn(): void
    {
        $this->signUp();
        $this->withSession()->get('auth/a/show');
        $this->withSession()->submit('auth/a/verify', ['token' => $this->sentCode()]);

        $result = $this->withSession()->submit('login', ['email' => self::EMAIL, 'password' => self::PASSWORD]);

        $result->assertRedirectTo('account');
    }

    public function testAFailedEmailKeepsThePageUp(): void
    {
        $this->signUp();

        service('email')->returnValue = false;

        $page = $this->withSession()->get('auth/a/show');

        $page->assertOK();
        $page->assertSee('We could not send your code just now');
        $this->assertLogged('error', 'The sign-up code could not be emailed. Check the email settings in .env.');
    }

    public function testCodeAttemptsAreRateLimited(): void
    {
        for ($attempt = 1; $attempt <= 10; $attempt++) {
            $this->get('auth/a/show');
        }

        $this->get('auth/a/show')->assertStatus(429);
    }

    private function signUp(): \CodeIgniter\Test\TestResponse
    {
        return $this->submit('register', [
            'name'             => 'Asha Menon',
            'email'            => self::EMAIL,
            'password'         => self::PASSWORD,
            'password_confirm' => self::PASSWORD,
        ]);
    }

    private function sentCode(): string
    {
        preg_match('/letter-spacing:8px;">(\d{6})</', service('email')->archive['body'], $match);

        return $match[1] ?? '';
    }

    private function customer(): User
    {
        return auth()->getProvider()->findByCredentials(['email' => self::EMAIL]);
    }
}
