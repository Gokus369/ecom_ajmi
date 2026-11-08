<?php

namespace App\Controllers;

use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\UserIdentityModel;
use Config\Google;

/**
 * Google opens only accounts it created, found by Google's ID for the person.
 *
 * It never joins an account that already uses the address. Addresses are not
 * verified at sign up, so someone could register a stranger's Gmail first, and
 * joining would leave their password working on that stranger's account. This
 * also means Google can never open an admin account.
 */
class GoogleSignIn extends BaseController
{
    private const SESSION_KEY   = 'google_sign_in';
    private const IDENTITY_TYPE = 'google';

    public function start(): RedirectResponse
    {
        $this->requireConnection();

        if (auth()->loggedIn()) {
            return redirect()->route('account');
        }

        $state    = bin2hex(random_bytes(16));
        $verifier = bin2hex(random_bytes(32));

        session()->set(self::SESSION_KEY, ['state' => $state, 'verifier' => $verifier]);

        return redirect()->to(service('google')->authorizeUrl($state, $verifier));
    }

    public function callback(): RedirectResponse
    {
        $this->requireConnection();

        if (auth()->loggedIn()) {
            return redirect()->route('account');
        }

        // Taken out first, so a return link can only ever be used once.
        $pending = session(self::SESSION_KEY);
        session()->remove(self::SESSION_KEY);

        if ($this->request->getGet('error') !== null) {
            return $this->refuse('Google sign-in was cancelled.');
        }

        $state = $this->request->getGet('state');
        $code  = $this->request->getGet('code');

        if (! is_array($pending) || ! is_string($state) || ! is_string($code) || $code === '' || ! hash_equals($pending['state'], $state)) {
            return $this->refuse('That sign-in attempt has expired. Please try again.');
        }

        $profile = service('google')->profile($code, $pending['verifier']);

        if (! $this->isTrustworthy($profile)) {
            return $this->refuse('We could not sign you in with Google. Please try again.');
        }

        $user = $this->findCustomer($profile['sub']);

        if ($user === null) {
            if (auth()->getProvider()->findByCredentials(['email' => $profile['email']]) !== null) {
                return $this->refuse('An account with this email already exists. Please sign in with your password.');
            }

            $user = $this->createCustomer($profile);
        }

        if ($user->isBanned()) {
            return $this->refuse('We could not sign you in with Google. Please try again.');
        }

        auth()->login($user);

        return redirect()->to(config('Auth')->loginRedirect());
    }

    private function requireConnection(): void
    {
        if (! config(Google::class)->isConnected()) {
            throw PageNotFoundException::forPageNotFound();
        }
    }

    /**
     * @param array<string, mixed>|null $profile
     */
    private function isTrustworthy(?array $profile): bool
    {
        return is_string($profile['sub'] ?? null) && $profile['sub'] !== ''
            && is_string($profile['email'] ?? null) && $profile['email'] !== ''
            && ($profile['email_verified'] ?? false) === true;
    }

    private function findCustomer(string $googleId): ?User
    {
        $identity = model(UserIdentityModel::class)->getIdentityBySecret(self::IDENTITY_TYPE, $googleId);

        return $identity === null ? null : auth()->getProvider()->findById($identity->user_id);
    }

    /**
     * @param array<string, mixed> $profile
     */
    private function createCustomer(array $profile): User
    {
        $users = auth()->getProvider();
        $name  = is_string($profile['name'] ?? null) ? mb_substr($profile['name'], 0, 100) : '';

        // A long random password nobody is ever told, so this account opens only through Google.
        $user = new User([
            'email'    => mb_strtolower($profile['email']),
            'password' => bin2hex(random_bytes(32)),
            'name'     => $name === '' ? null : $name,
        ]);

        // Shield needs the column set for accounts without a username.
        $user->username = null;

        $users->save($user);

        $user = $users->findById($users->getInsertID());

        $users->addToDefaultGroup($user);
        $user->activate();

        model(UserIdentityModel::class)->insert([
            'user_id' => $user->id,
            'type'    => self::IDENTITY_TYPE,
            'secret'  => $profile['sub'],
        ]);

        return $user;
    }

    private function refuse(string $message): RedirectResponse
    {
        return redirect()->route('login')->with('error', $message);
    }
}
