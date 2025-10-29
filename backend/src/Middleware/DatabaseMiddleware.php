<?php
namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Psr7\Response as SlimResponse;
use PDO;
use PDOException;

class DatabaseMiddleware
{
    public function __invoke(Request $request, Handler $handler): Response
    {
        $appEnv = $_ENV['APP_ENV'] ?? 'production';

        try {
            $dsn  = $_ENV['DB_DSN']  ?? '';
            $user = $_ENV['DB_USER'] ?? '';
            $pass = $_ENV['DB_PASS'] ?? '';

            if (empty($dsn) || empty($user)) {
                throw new \Exception("Database configuration missing");
            }

            // Quick test connection
            new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);

        } catch (PDOException $e) {
            $payload = [
                'status'    => false,
                'message'   => 'Database connection failed',
            ];

            if ($appEnv === 'dev') {
                $payload['meta'] = ['error' => $e->getMessage()];
            }

            $response = new SlimResponse();
            $response->getBody()->write(json_encode($payload));
            return $response->withHeader('Content-Type', 'application/json')
                            ->withStatus(500);

        } catch (\Exception $e) {
            $payload = [
                'status'    => false,
                'message'   => 'Invalid DB configuration',
            ];

            if ($appEnv === 'dev') {
                $payload['meta'] = ['error' => $e->getMessage()];
            }

            $response = new SlimResponse();
            $response->getBody()->write(json_encode($payload));
            return $response->withHeader('Content-Type', 'application/json')
                            ->withStatus(500);
        }

        return $handler->handle($request);
    }
}
