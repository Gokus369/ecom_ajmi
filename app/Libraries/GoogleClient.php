<?php

namespace App\Libraries;

use CodeIgniter\HTTP\Exceptions\HTTPException;
use Config\Google;

/**
 * Talks to Google's sign-in service. The profile comes straight from Google over
 * TLS, so it needs no signature check. A failure is logged, never thrown.
 */
class GoogleClient
{
    private const AUTHORIZE_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_URL     = 'https://oauth2.googleapis.com/token';
    private const PROFILE_URL   = 'https://openidconnect.googleapis.com/v1/userinfo';

    /** Where to send the shopper to choose their Google account. */
    public function authorizeUrl(string $state, string $verifier): string
    {
        return self::AUTHORIZE_URL . '?' . http_build_query([
            'client_id'             => config(Google::class)->clientId,
            'redirect_uri'          => $this->redirectUri(),
            'response_type'         => 'code',
            'scope'                 => 'openid email profile',
            'state'                 => $state,
            'code_challenge'        => rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '='),
            'code_challenge_method' => 'S256',
            'prompt'                => 'select_account',
        ]);
    }

    /**
     * Swaps the code Google sent back for the shopper's profile.
     *
     * @return array<string, mixed>|null null when Google refuses or cannot be reached
     */
    public function profile(string $code, string $verifier): ?array
    {
        $config = config(Google::class);

        try {
            $token = service('curlrequest')->post(self::TOKEN_URL, [
                'form_params' => [
                    'code'          => $code,
                    'client_id'     => $config->clientId,
                    'client_secret' => $config->clientSecret,
                    'redirect_uri'  => $this->redirectUri(),
                    'grant_type'    => 'authorization_code',
                    'code_verifier' => $verifier,
                ],
                'http_errors' => false,
                'timeout'     => 10,
            ]);

            $accessToken = json_decode($token->getBody(), true)['access_token'] ?? null;

            if (! is_string($accessToken)) {
                log_message('error', 'Google sign-in failed with HTTP ' . $token->getStatusCode());

                return null;
            }

            $response = service('curlrequest')->get(self::PROFILE_URL, [
                'headers'     => ['Authorization' => "Bearer {$accessToken}"],
                'http_errors' => false,
                'timeout'     => 10,
            ]);
        } catch (HTTPException $e) {
            log_message('error', 'Google sign-in failed: ' . $e->getMessage());

            return null;
        }

        $profile = json_decode($response->getBody(), true);

        return $response->getStatusCode() === 200 && is_array($profile) ? $profile : null;
    }

    private function redirectUri(): string
    {
        return url_to('auth.google.callback');
    }
}
