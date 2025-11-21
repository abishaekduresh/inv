<?php
namespace App\Controller;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Helper\ApiHelper;
use Dflydev\FigCookies\SetCookie;
use Dflydev\FigCookies\FigResponseCookies;
use Dflydev\FigCookies\Modifier\SameSite;

class AuthController
{
    private string $backendApiUrl;
    private ApiHelper $apiHelper;

    public function __construct()
    {
        $this->backendApiUrl = $_ENV['BACKEND_API_URL'] ?? '';
        if (empty($this->backendApiUrl)) {
            throw new \RuntimeException('BACKEND_API_URL is not set in environment.');
        }

        $this->apiHelper = new ApiHelper();
    }

    /**
     * Handle user login
     */
    public function loginUser(Request $request, Response $response, array $args): Response
    {
        try {
            $contentType = $request->getHeaderLine('Content-Type');
            $payload = [];

            // Parse JSON payload
            if (stripos($contentType, 'application/json') !== false) {
                $payload = json_decode((string) $request->getBody(), true) ?? [];
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return $this->apiHelper->jsonResponse($response, [
                        'status'    => false,
                        'httpCode'  => 400,
                        'body'      => null,
                        'headers'   => [],
                        'error'     => 'Invalid JSON payload'
                    ]);
                }
            }
            // Parse multipart/form-data
            elseif (stripos($contentType, 'multipart/form-data') !== false) {
                $payload = $request->getParsedBody() ?? [];
                $files = $request->getUploadedFiles() ?? [];
                $payload['files'] = $this->apiHelper->normalizeFiles($files);
            } else {
                return $this->apiHelper->jsonResponse($response, [
                    'status'    => false,
                    'httpCode'  => 415,
                    'body'      => null,
                    'headers'   => [],
                    'error'     => 'Unsupported Content-Type. Use JSON or multipart/form-data'
                ]);
            }

            // Call backend API
            $apiUrl = rtrim($this->backendApiUrl, '/') . '/api/auth/users/login';
            $result = $this->apiHelper->request($apiUrl, 'POST', $payload);

            // Forward API response exactly as-is
            return $this->apiHelper->jsonResponse($response, $result);

        } catch (\Throwable $e) {
            return $this->apiHelper->jsonResponse($response, [
                'status'    => false,
                'httpCode'  => 500,
                'body'      => null,
                'headers'   => [],
                'error'     => 'Server Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Logout user by expiring the JWT cookie
     */
    public function logout(Request $request, Response $response, array $args): Response
    {
        $cookiePath = $_ENV['COOKIE_PATH'] ?? '/';
        $cookieDomain = $_ENV['COOKIE_DOMAIN'] ?? null;
        $cookieSameSite = $_ENV['COOKIE_SAMESITE'] ?? 'Lax';

        // Convert SameSite string to SameSite object
        switch (strtolower($cookieSameSite)) {
            case 'strict':
                $cookieSameSiteModifier = SameSite::strict();
                break;
            case 'none':
                $cookieSameSiteModifier = SameSite::none();
                break;
            case 'lax':
            default:
                $cookieSameSiteModifier = SameSite::lax();
                break;
        }

        // Loop through all cookies and expire them
        foreach ($_COOKIE as $name => $value) {
            $expiredCookie = SetCookie::create($name)
                ->withValue('')
                ->withPath($cookiePath)
                ->withDomain($cookieDomain)
                ->withHttpOnly(true)
                ->withSecure(true)
                ->withSameSite($cookieSameSiteModifier)
                ->withExpires(time() - 3600);

            $response = FigResponseCookies::set($response, $expiredCookie);

            // Also unset from PHP's $_COOKIE for current request lifecycle
            unset($_COOKIE[$name]);
        }

        // Build response body
        $payload['body'] = [
            'status'  => true,
            'message' => 'Logged out successfully',
        ];

        return $this->apiHelper->jsonResponse($response, $payload);
    }
}
