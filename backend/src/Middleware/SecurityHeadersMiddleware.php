<?php
namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;

class SecurityHeadersMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        // Prevent clickjacking
        $response = $response->withHeader('X-Frame-Options', 'DENY');

        // Prevent MIME type sniffing
        $response = $response->withHeader('X-Content-Type-Options', 'nosniff');

        // XSS protection (legacy, some older browsers)
        $response = $response->withHeader('X-XSS-Protection', '1; mode=block');

        // Referrer policy
        $response = $response->withHeader('Referrer-Policy', 'no-referrer-when-downgrade');

        // Strict transport security (only use on HTTPS)
        $response = $response->withHeader('Strict-Transport-Security', 'max-age=63072000; includeSubDomains; preload');

        // Content Security Policy (CSP) — adjust domains as needed
        $csp = "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:;";
        $response = $response->withHeader('Content-Security-Policy', $csp);

        return $response;
    }
}
