<?php
namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Psr7\Response as SlimResponse;

class EnvValidationMiddleware
{
    private array $requiredKeys;

    public function __construct(array $requiredKeys)
    {
        $this->requiredKeys = $requiredKeys;
    }

    public function __invoke(Request $request, Handler $handler): Response
    {
        $missing = [];
        $appEnv = $_ENV['APP_ENV'] ?? 'production';

        foreach ($this->requiredKeys as $key) {
            if (!isset($_ENV[$key]) || trim($_ENV[$key]) === '') {
                $missing[] = $key;
            }
        }

        if (!empty($missing)) {
            $payload = [
                'status'    => false,
                'http_code' => 500,
                'message'   => 'Missing required environment variables',
            ];

            // Add meta info only in dev
            if ($appEnv === 'dev') {
                $payload['meta'] = ['missing' => $missing];
            }

            $response = new SlimResponse();
            $response->getBody()->write(json_encode($payload));
            return $response->withHeader('Content-Type', 'application/json')
                            ->withStatus(500);
        }

        return $handler->handle($request);
    }
}
