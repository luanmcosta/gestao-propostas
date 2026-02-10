<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

class ApiRateLimitFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $capacity = (int) env('rateLimit.api.capacity', 120);
        $window   = (int) env('rateLimit.api.windowSeconds', 60);

        if ($capacity < 1 || $window < 1) {
            return null;
        }

        $ipAddress = $request->getIPAddress() ?: 'unknown';
        $bucketKey = 'api_' . hash('sha256', $ipAddress);
        $throttler = service('throttler');

        if ($throttler->check($bucketKey, $capacity, $window)) {
            return null;
        }

        $retryAfter = max(1, $throttler->getTokenTime());

        return Services::response()
            ->setStatusCode(429)
            ->setHeader('Retry-After', (string) $retryAfter)
            ->setHeader('X-RateLimit-Limit', (string) $capacity)
            ->setHeader('X-RateLimit-Window', (string) $window)
            ->setJSON([
                'status'   => 429,
                'error'    => 'rate_limit_exceeded',
                'messages' => [
                    'error' => 'Rate limit exceeded. Try again later.',
                ],
            ]);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
