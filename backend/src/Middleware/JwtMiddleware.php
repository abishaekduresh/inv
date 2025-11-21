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
        // === Get Authorization header ===
        $authHeader = $request->getHeaderLine('Authorization');

        if (empty($authHeader)) {
            return $this->unauthorized(
                "Authorization header missing",
                "The Authorization header must contain a valid Bearer token."
            );
        }

        // === Validate Bearer prefix ===
        if (stripos($authHeader, 'Bearer ') !== 0) {
            return $this->unauthorized(
                "Invalid Authorization header format",
                "Header must start with 'Bearer ' followed by the token."
            );
        }

        $token = trim(substr($authHeader, 7)); // remove "Bearer " prefix

        if (!$token) {
            return $this->unauthorized(
                "JWT token missing",
                "Ensure you include a token after 'Bearer '."
            );
        }

        // === Verify Token via Helper ===
        $verification = $this->jwtHelper->verifyToken($token);

        // If verifyToken returns array (with status)
        if (is_array($verification) && !$verification['status']) {
            return $this->unauthorized(
                $verification['message'] ?? 'Invalid or expired token',
                $verification['help'] ?? 'Please log in again to obtain a new token.',
                $verification['error'] ?? null,
                $verification['httpCode'] ?? 401
            );
        }

        // Handle unexpected verification result
        if (empty($verification) || (is_array($verification) && empty($verification['data']))) {
            return $this->unauthorized(
                'Token verification failed.',
                'Please reauthenticate and try again.'
            );
        }

        // === Attach decoded JWT payload to request ===
        $decodedData = is_array($verification) ? $verification['data'] : $verification;
        $request = $request->withAttribute('jwt', $decodedData['jwt'] ?? 'JWT');
        $request = $request->withAttribute('userId', $decodedData['userId'] ?? 'UserID');
        $request = $request->withAttribute('businessId', $decodedData['businessId'] ?? 'BusinessID');

        // === Continue request flow ===
        return $handler->handle($request);
    }

    /**
     * Return a detailed 401 Unauthorized response
     */
    private function unauthorized(
        string $message,
        ?string $help = null,
        ?string $error = null,
        int $statusCode = 401
    ): Response {
        $response = new SlimResponse();

        $payload = [
            'status'  => false,
            'message' => $message,
            'help'    => $help ?? 'Please logout and login again.',
        ];

        if (!empty($error)) {
            $payload['error'] = $error;
        }

        $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($statusCode);
    }
}
