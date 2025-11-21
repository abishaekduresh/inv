<?php
namespace App\Controller;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Helper\ApiHelper;

class BusinessController
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

    public function getBusiness(Request $request, Response $response, array $args): Response
    {
        try {
            $payload = [];
            $payload = $queryParams = $request->getQueryParams();

            // Call backend API
            $apiUrl = rtrim($this->backendApiUrl, '/') . '/api/business';
            $result = $this->apiHelper->request($apiUrl, 'GET', [], [], $payload);

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

    public function createBusiness(Request $request, Response $response, array $args): Response
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
            $apiUrl = rtrim($this->backendApiUrl, '/') . '/api/Business';
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

    public function updateBusiness(Request $request, Response $response, array $args): Response
    {
        try {
            $contentType = $request->getHeaderLine('Content-Type');
            $businessId = $args['businessId'] ?? null;

            if (!$businessId) {
                return $this->apiHelper->jsonResponse($response, [
                    'status'    => false,
                    'httpCode'  => 400,
                    'body'      => null,
                    'error'     => 'Missing business ID.'
                ]);
            }

            $apiUrl = rtrim($this->backendApiUrl, '/') . '/api/business/' . $businessId;

            // === Handle multipart/form-data ===
            $contentType = $request->getHeaderLine('Content-Type');
            $method = strtoupper($request->getMethod());
            $payload = [];
            $files = [];
            $postData = [];

            // === Handle PUT multipart manually ===
            if ($method === 'PUT' && stripos($contentType, 'multipart/form-data') !== false) {
                if (!preg_match('/boundary=(.*)$/', $contentType, $matches)) {
                    return $this->apiHelper->jsonResponse($response, [
                        'status' => false,
                        'message' => 'Invalid multipart/form-data (missing boundary)',
                        'httpCode' => 400,
                    ]);
                }

                $boundary = trim($matches[1]);
                $body = (string)$request->getBody();
                $parts = preg_split('/--' . preg_quote($boundary, '/') . '/', $body);

                $payload = [];
                $files = [];

                foreach ($parts as $part) {
                    $part = trim($part);
                    if ($part === '' || $part === '--') continue;

                    if (preg_match('/name="([^"]+)"/', $part, $nameMatch)) {
                        $name = $nameMatch[1];
                        [$headers, $content] = preg_split("/\r\n\r\n/", $part, 2) + [null, null];
                        $content = rtrim($content ?? '', "\r\n");

                        if (preg_match('/filename="([^"]+)"/', $headers, $fileMatch)) {
                            $filename = trim($fileMatch[1]);
                            $tmpPath = sys_get_temp_dir() . '/' . uniqid('upload_', true);
                            file_put_contents($tmpPath, $content);

                            $files[$name] = [
                                'tmp_name' => $tmpPath,
                                'name' => $filename,
                                'type' => mime_content_type($tmpPath),
                                'size' => filesize($tmpPath),
                                'error' => UPLOAD_ERR_OK
                            ];
                        } else {
                            $payload[$name] = trim($content);
                        }
                    }
                }

                // Send parsed payload and files to API
                $result = $this->apiHelper->request($apiUrl, 'PUT', $payload, [], [], 'form', $files);
            }
            // === Handle JSON ===
            elseif (stripos($contentType, 'application/json') !== false) {
                $payload = json_decode((string) $request->getBody(), true) ?? [];
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return $this->apiHelper->jsonResponse($response, [
                        'status'   => false,
                        'httpCode' => 400,
                        'error'    => 'Invalid JSON payload'
                    ]);
                }

                $result = $this->apiHelper->request($apiUrl, 'PUT', $payload, [], [], 'json');
            }

            else {
                return $this->apiHelper->jsonResponse($response, [
                    'status'   => false,
                    'httpCode' => 415,
                    'error'    => 'Unsupported Content-Type. Use JSON or multipart/form-data'
                ]);
            }

            return $this->apiHelper->jsonResponse($response, $result);

        } catch (\Throwable $e) {
            return $this->apiHelper->jsonResponse($response, [
                'status'    => false,
                'httpCode'  => 500,
                'error'     => 'Server Error: ' . $e->getMessage()
            ]);
        }
    }

    public function deleteBusiness(Request $request, Response $response, array $args): Response
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

            // Call backend API
            $apiUrl = rtrim($this->backendApiUrl, '/') . '/api/business/' . $args['businessId'];
            $result = $this->apiHelper->request($apiUrl, 'DELETE', $payload);

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

    public function fetchDashboardStats(Request $request, Response $response, array $args): Response
    {
        try {
            $payload = [];
            $payload = $queryParams = $request->getQueryParams();

            // Call backend API
            $apiUrl = rtrim($this->backendApiUrl, '/') . '/api/business/stats';
            $result = $this->apiHelper->request($apiUrl, 'GET', [], [], $payload);

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

    public function fetchActivityLogs(Request $request, Response $response, array $args): Response
    {
        try {
            $payload = [];
            $payload = $queryParams = $request->getQueryParams();

            // Call backend API
            $apiUrl = rtrim($this->backendApiUrl, '/') . '/api/business/activity/logs';
            $result = $this->apiHelper->request($apiUrl, 'GET', [], [], $payload);

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
}
