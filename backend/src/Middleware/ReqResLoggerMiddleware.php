<?php
namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use App\Helper\DatabaseHelper;
use App\Helper\TimestampHelper;

class ReqResLoggerMiddleware
{
    private DatabaseHelper $pdo;
    private TimestampHelper $timestampHelper;

    public function __construct()
    {
        $this->pdo = new DatabaseHelper();
        $this->timestampHelper = new TimestampHelper();
    }

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $conn = $this->pdo->getConnection();
        $currentTimestamp = $this->timestampHelper->getCurrentUnixTimestamp();

        // --- Request Metadata ---
        $jwt    = $request->getAttribute("jwt");
        $userId = $jwt->sub ?? "guest";
        $method = $request->getMethod();
        $uri    = (string) $request->getUri();
        $ip     = $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown';
        $agent  = $request->getHeaderLine('User-Agent');

        // --- Capture Request Body ---
        $requestBody = null;
        if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $requestBody = (string) $request->getBody();
        } else {
            // Log query params for GET/DELETE
            $queryParams = $request->getQueryParams();
            $requestBody = json_encode($queryParams);
        }
        // $logRequest = mb_strimwidth($requestBody, 0, 2000, "...");
        $logRequest = $requestBody;

        // --- Handle Request ---
        $response = $handler->handle($request);

        // --- Capture Response ---
        $body = (string) $response->getBody();
        // $logResponse = mb_strimwidth($body, 0, 2000, "...");
        $logResponse = $body;

        // --- Insert Log into DB ---
        $stmt = $conn->prepare("
            INSERT INTO req_res_logs 
                (user_id, method, uri, ip, user_agent, request_body, response_body, created_at)
            VALUES (:user_id, :method, :uri, :ip, :agent, :req, :res, :created_at)
        ");

        $stmt->execute([
            ':user_id' => $userId,
            ':method'  => $method,
            ':uri'     => $uri,
            ':ip'      => $ip,
            ':agent'   => $agent,
            ':req'     => $logRequest,
            ':res'     => $logResponse,
            ':created_at' => $currentTimestamp
        ]);

        return $response;
    }
}
