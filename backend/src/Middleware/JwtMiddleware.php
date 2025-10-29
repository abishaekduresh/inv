<?php
namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use App\Helper\JwtHelper;
use Slim\Psr7\Response as SlimResponse;

class JwtMiddleware
{
    private JwtHelper $jwtHelper;

    public function __construct(JwtHelper $jwtHelper)
    {
        $this->jwtHelper = $jwtHelper;
    }

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        // Get Authorization header
        $authHeader = $request->getHeaderLine('Authorization');

        if (empty($authHeader)) {
            return $this->unauthorized("Authorization header missing");
        }

        // Validate Bearer prefix
        if (stripos($authHeader, 'Bearer ') !== 0) {
            return $this->unauthorized("Authorization header must start with 'Bearer '");
        }

        $token = trim(substr($authHeader, 7)); // remove "Bearer " prefix

        if (!$token) {
            return $this->unauthorized("JWT token missing after 'Bearer ' prefix");
        }

        try {
            $decoded = $this->jwtHelper->verifyToken($token);
        } catch (\Exception $e) {
            return $this->unauthorized("Invalid or expired token");
        }

        if (!$decoded) {
            return $this->unauthorized("Invalid or expired token", $decoded);
        }

        // Attach decoded JWT payload to request
        $request = $request->withAttribute('jwt', $decoded);

        // Pass request to next middleware/controller
        return $handler->handle($request);
    }

    private function unauthorized(string $message, $decoded = null): Response
    {
        $response = new SlimResponse();
        $response->getBody()->write(json_encode([
            'status'  => false,
            'message' => $message,
            'help'    => 'Please logout, try again'
        ]));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(401);
    }
}
