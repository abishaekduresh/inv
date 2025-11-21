<?php
namespace App\Controller;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Psr7\Stream;
use GuzzleHttp\Client;

class CommonController
{
    /**
     * Proxy uploads from core backend → app backend
     */
    public function getUploads(Request $request, Response $response, array $args): Response
    {
        $path = $args['path'] ?? '';
        $queryParams = $request->getQueryParams();
        $wantBase64 = isset($queryParams['base64']);

        // Build the Core Backend URL
        $coreBackendBaseUrl = rtrim($_ENV['BACKEND_API_URL'] ?? null, '/');
        $coreUrl = $coreBackendBaseUrl . '/uploads/' . ltrim($path, '/');

        // Configure Guzzle client
        $client = new Client([
            'timeout' => 15.0,
            'http_errors' => false, // prevent exceptions on 4xx/5xx
            'verify' => ($_ENV['APP_ENV'] ?? 'development') === 'production',
        ]);

        try {
            // Fetch file from Core Backend
            $res = $client->get($coreUrl, ['stream' => true]);
            $statusCode = $res->getStatusCode();

            if ($statusCode !== 200) {
                $response->getBody()->write("Core backend returned status {$statusCode}");
                return $response->withStatus($statusCode);
            }

            $mime = $res->getHeaderLine('Content-Type') ?: 'application/octet-stream';
            $fileData = $res->getBody()->getContents();

            // Base64 version (optional)
            if ($wantBase64) {
                $base64 = 'data:' . $mime . ';base64,' . base64_encode($fileData);
                $response->getBody()->write($base64);
                return $response
                    ->withHeader('Content-Type', 'text/plain')
                    ->withHeader('Cache-Control', 'public, max-age=86400');
            }

            // Stream version (default)
            $stream = new Stream(fopen('php://temp', 'r+'));
            $stream->write($fileData);
            $stream->rewind();

            return $response
                ->withHeader('Content-Type', $mime)
                ->withHeader('Cache-Control', 'public, max-age=86400')
                ->withBody($stream);

        } catch (\Throwable $e) {
            $response->getBody()->write(json_encode([
                'status' => false,
                'message' => 'Error fetching from core backend: ' . $e->getMessage(),
            ]));
            return $response->withStatus(500);
        }
    }
}
