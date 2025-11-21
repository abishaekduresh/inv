<?php
namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use App\Helper\LoggerHelper;
use App\Helper\ActionMapperHelper;
use Slim\Routing\RouteContext;

class ActivityLoggerMiddleware
{
    public function __invoke(ServerRequestInterface $request, $handler): ResponseInterface
    {
        // Let the controller handle the request first
        $response = $handler->handle($request);

        // Read and rewind the response body
        $bodyStream = $response->getBody();
        $bodyStream->rewind();                    // move pointer to start
        $responseContent = $bodyStream->getContents(); // now you can read it safely

        // Decode response if it’s JSON
        $decodedResponse = json_decode($responseContent, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $decodedResponse = ['raw' => $responseContent];
        }

        // Collect route info
        try {
            $routeContext = RouteContext::fromRequest($request);
            $route = $routeContext->getRoute();
        } catch (\Throwable $e) {
            $route = null;
        }

        $path   = $request->getUri()->getPath();
        $method = strtoupper($request->getMethod());
        $actionName = ActionMapperHelper::getActionName($method, $path);

        if ($actionName === 'API Request: ' . $method . ' ' . $path && $route) {
            $actionName = $route->getName() ?: $actionName;
        }

        // Extract JWT or fallback to attributes
        $jwt = $request->getAttribute('jwt');
        $userId = null;
        $businessId = null;

        if (is_object($jwt)) {
            $userId = $jwt->sub ?? null;
            $businessId = $jwt->busid ?? null;
        } elseif (is_array($jwt)) {
            $userId = $jwt['sub'] ?? null;
            $businessId = $jwt['busid'] ?? null;
        } else {
            $userId = $request->getAttribute('user_id') ?? null;
            $businessId = $request->getAttribute('business_id') ?? null;
        }

        // Filter request body (you can sanitize passwords here)
        $requestData = $request->getParsedBody() ?: [];
        unset($requestData['password']); // optional safety line

        // Log everything
        $logger = new LoggerHelper();
        // Build response_data dynamically
        $responseData = [
            'status'  => $decodedResponse['status']  ?? null,
            'message' => $decodedResponse['message'] ?? ($decodedResponse['raw'] ?? 'No message'),
        ];

        // Only add 'error' key if it exists
        if (isset($decodedResponse['error']) && $decodedResponse['error'] !== null) {
            $responseData['error'] = $decodedResponse['error'];
        }

        // Only add 'help' key if it exists
        if (isset($decodedResponse['help']) && $decodedResponse['help'] !== null) {
            $responseData['help'] = $decodedResponse['help'];
        }
        $logger->log([
            'user_id'       => $userId,
            'business_id'   => $businessId,
            'action'        => $decodedResponse['message'] ?? $actionName,
            'method'        => $method,
            'endpoint'      => $path,
            'ip_address'    => $_SERVER['REMOTE_ADDR'] ?? null,
            'request_data'  => $requestData,
            'response_data' => $responseData,
        ]);

        // Rewind stream again so Slim can send response normally
        $response->getBody()->rewind();

        return $response;
    }
}
