<?php
namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Slim\Psr7\Response;

class RateLimitMiddleware implements MiddlewareInterface
{
    private int $maxRequests;
    private int $window; // seconds
    private string $storageFile;

    public function __construct(int $maxRequests = 60, int $window = 60, string $storageFile = __DIR__ . '/../../storage/ratelimit.json')
    {
        $this->maxRequests = $maxRequests;
        $this->window = $window;
        $this->storageFile = $storageFile;

        // Ensure storage directory exists
        $dir = dirname($this->storageFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        // Ensure JSON file exists
        if (!file_exists($this->storageFile)) {
            file_put_contents($this->storageFile, json_encode([]));
        }
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $ip = $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown';
        $now = time();

        $data = $this->readData();

        if (!isset($data[$ip]) || $now > $data[$ip]['expires']) {
            // New window
            $data[$ip] = [
                'count'   => 1,
                'expires' => $now + $this->window
            ];
        } else {
            // Increase counter
            $data[$ip]['count']++;
        }

        $this->writeData($data);

        if ($data[$ip]['count'] > $this->maxRequests) {
            $retryAfter = max(0, $data[$ip]['expires'] - $now);

            $response = new Response();
            $response->getBody()->write(json_encode([
                'status'    => false,
                'message'   => 'Too Many Requests. Please try again later.',
                'retry_after_seconds' => $retryAfter
            ]));

            return $response
                ->withStatus(429)
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('Retry-After', (string) $retryAfter);
        }

        return $handler->handle($request);
    }

    private function readData(): array
    {
        $content = @file_get_contents($this->storageFile);
        if ($content === false || trim($content) === '') {
            return [];
        }
    
        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            // Reset corrupted file
            $data = [];
            file_put_contents($this->storageFile, json_encode($data, JSON_PRETTY_PRINT));
        }
    
        return $data;
    }

    private function writeData(array $data): void
    {
        file_put_contents($this->storageFile, json_encode($data, JSON_PRETTY_PRINT));
    }
}
