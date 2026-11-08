<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Per-IP rate limit for public forms: ['filter' => 'throttle:10'] allows ten a minute.
 */
class Throttle implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $limit = (int) ($arguments[0] ?? 5);
        $key   = md5($request->getUri()->getPath() . '|' . $request->getIPAddress());

        if (service('throttler')->check($key, $limit, MINUTE) === false) {
            return service('response')
                ->setStatusCode(429)
                ->setBody('Too many attempts. Please wait a minute and try again.');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
