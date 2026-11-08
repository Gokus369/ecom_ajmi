<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Shield treats a second sign-in as a fatal error, so an old sign-in or sign-up page
 * posting from another tab, or after the Back button, is sent where it belongs instead.
 */
class AlreadySigningIn implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (auth()->loggedIn()) {
            return redirect()->to(config('Auth')->loginRedirect());
        }

        if (auth('session')->getAuthenticator()->hasAction()) {
            return redirect()->route('auth-action-show');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
