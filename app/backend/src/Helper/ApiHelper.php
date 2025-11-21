<?php
namespace App\Helper;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Dflydev\FigCookies\SetCookie;
use Dflydev\FigCookies\FigResponseCookies;
use Dflydev\FigCookies\FigRequestCookies;
use Dflydev\FigCookies\Modifier\SameSite;

/**
 * ApiHelper provides a robust cURL wrapper for making external HTTP requests
 * and includes methods for handling PSR-7 responses (like in Slim/Laminas)
 * and managing JWT tokens via HTTP-only cookies.
 */
class ApiHelper
{
    /**
     * Store the JWT token for immediate use in the current request
     */
    private ?string $currentToken = null;

    public function __construct(){}

    /**
     * Make a cURL API request
     */
    public function request(
        string $url,
        string $method = 'GET',
        array $data = [],
        array $headers = [],
        array $query = [],
        string $contentType = 'json',
        array $files = []   // files parameter
    ): array {
        $method = strtoupper($method);

        // === Build query params ===
        if (!empty($query)) {
            $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($query);
        }

        $ch = curl_init();
        $curlOptions = [
            CURLOPT_URL             => $url,
            CURLOPT_CUSTOMREQUEST   => $method,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_HEADER          => true,
            CURLOPT_TIMEOUT         => 30,
        ];

        // === SSL config ===
        $isProduction = ($_ENV['APP_ENV'] ?? 'development') === 'production';
        $curlOptions[CURLOPT_SSL_VERIFYPEER] = $isProduction;
        $curlOptions[CURLOPT_SSL_VERIFYHOST] = $isProduction ? 2 : 0;

        // === Auth header ===
        $token = $this->currentToken ?? ($_COOKIE['accessToken'] ?? null);
        if (!empty($token)) {
            $headers[] = "Authorization: Bearer {$token}";
        }

        // === Prepare payload based on content type ===
        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE']) && (!empty($data) || !empty($files))) {
            switch ($contentType) {
                case 'json':
                    $payload = json_encode($data);
                    $headers[] = "Content-Type: application/json";
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                    break;

                case 'form':
                    // Remove any existing Content-Type (so cURL sets multipart automatically)
                    $headers = array_filter($headers, fn($h) => !str_starts_with($h, 'Content-Type'));
                    $formData = [];

                    // Add form fields
                    foreach ($data as $key => $value) {
                        $formData[$key] = $value;
                    }

                    // ✅ Add file fields using CURLFile
                    foreach ($files as $key => $file) {
                        if (!empty($file['tmp_name']) && file_exists($file['tmp_name'])) {
                            $formData[$key] = new \CURLFile(
                                $file['tmp_name'],
                                $file['type'] ?? 'application/octet-stream',
                                $file['name'] ?? basename($file['tmp_name'])
                            );
                        }
                    }

                    curl_setopt($ch, CURLOPT_POSTFIELDS, $formData);
                    break;

                case 'urlencoded':
                    $payload = http_build_query($data);
                    $headers[] = "Content-Type: application/x-www-form-urlencoded";
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                    break;

                default:
                    throw new \InvalidArgumentException("Unsupported content type: {$contentType}");
            }
        }

        // === Set headers ===
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        curl_setopt_array($ch, $curlOptions);

        // === Execute request ===
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        // === Handle errors ===
        if ($response === false) {
            return [
                'status'   => false,
                'httpCode' => $httpStatus ?: 502,
                'body'     => null,
                'headers'  => [],
                'error'    => $error,
            ];
        }

        // === Parse headers & body ===
        $rawHeaders = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        $parsedHeaders = [];
        foreach (explode("\r\n", $rawHeaders) as $line) {
            if (strpos($line, ':') !== false) {
                [$key, $value] = explode(':', $line, 2);
                $parsedHeaders[strtolower(trim($key))] = trim($value);
            }
        }

        $decoded = json_decode($body, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $body = $decoded;
        }

        return [
            'status'   => $httpStatus >= 200 && $httpStatus < 300,
            'httpCode' => $httpStatus,
            'body'     => $body,
            'headers'  => $parsedHeaders,
            'error'    => $error ?: null,
        ];
    }

    /**
     * JSON response for Slim with dynamic cookie handling
     */
    public function jsonResponse(Response $response, array $result): Response
    {
        $authorizationHeader = $result['headers']['authorization'] ?? null;

        if (!empty($authorizationHeader) && preg_match('/Bearer\s(\S+)/', $authorizationHeader, $matches)) {
            $token = $matches[1];
            $this->currentToken = $token;

            $appEnv = $_ENV['APP_ENV'] ?? 'development';

            // Determine secure flag
            $isSecure = false;
            if ($appEnv === 'production') {
                $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
                            ($_SERVER['SERVER_PORT'] == 443) ||
                            (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
            }

            // Cookie configuration
            $cookiePath = $_ENV['COOKIE_PATH'] ?? '/';
            $cookieDomain = $_ENV['COOKIE_DOMAIN'] ?? null;
            $cookieSameSite = $_ENV['COOKIE_SAMESITE'] ?? 'Lax';

            // Convert string to SameSite object
            switch (strtolower($cookieSameSite)) {
                case 'lax':
                    $cookieSameSiteModifier = SameSite::lax();
                    break;
                case 'strict':
                    $cookieSameSiteModifier = SameSite::strict();
                    break;
                case 'none':
                    $cookieSameSiteModifier = SameSite::none();
                    break;
                default:
                    $cookieSameSiteModifier = SameSite::lax();
                    break;
            }
            $expirySeconds = (int) ($_ENV['COOKIE_EXPIRY_SECONDS']);

            // Create cookie
            $cookie = SetCookie::create('accessToken')
                ->withValue($token)
                ->withPath($cookiePath)
                ->withDomain($cookieDomain)
                ->withHttpOnly(true)    // JS access
                ->withSecure($isSecure) // only send over HTTPS
                ->withSameSite($cookieSameSiteModifier)
                ->withExpires(time() + $expirySeconds);

            $response = FigResponseCookies::set($response, $cookie);
        }

        $response->getBody()->write(json_encode($result['body'] , JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        return $response->withHeader('Content-Type', 'application/json')
                        ->withStatus($result['httpCode'] ?? 200);
    }

    /**
     * Read JWT from request cookie using dflydev/fig-cookies
     */
    public function getTokenFromRequest(Request $request): ?string
    {
        $cookie = FigRequestCookies::get($request, 'accessToken');
        return $cookie ? $cookie->getValue() : null;
    }

    /**
     * Normalize uploaded files into array
     */
    private function normalizeFiles(array $files): array
    {
        $normalized = [];
        foreach ($files as $key => $file) {
            $normalized[$key] = [
                'name'      => $file->getClientFilename(),
                'type'      => $file->getClientMediaType(),
                'size'      => $file->getSize(),
                'tmp_name'  => $file->getStream()->getMetadata('uri'),
                'error'     => $file->getError()
            ];
        }
        return $normalized;
    }
}
