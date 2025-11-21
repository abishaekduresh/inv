<?php
namespace App\Controller;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Model\BusinessModel;
use Slim\Psr7\UploadedFile;
use Slim\Psr7\Stream;


class BusinessController
{
    private BusinessModel $businessModel;
    private string $file;
    private string $name;
    private string $type;
    private int $size;
    private int $error;

    public function __construct()
    {
        $this->businessModel = new BusinessModel();
        
    }
    
    public function getBusiness(Request $request, Response $response, array $args): Response
    {
        $queryParams = $request->getQueryParams();

        $data = [
            'searchQuery' => $queryParams['q'] ?? null,
            'businessId'  => isset($queryParams['id']) ? strtoupper($queryParams['id']) : null,
            'phone'       => isset($queryParams['ph']) ? (int) $queryParams['ph'] : null,
            'status'      => $queryParams['sts'] ?? 'active',
            'order'       => strtoupper($queryParams['ord'] ?? 'DESC'),
            'page'        => (int)($queryParams['page'] ?? 1),
            'limit'       => (int)($queryParams['limit'] ?? 25),
        ];

        if (!empty($data['phone']) && !preg_match('/^\d{10}$/', (string)$data['phone'])) {
            return $this->jsonResponse($response, [
                'status' => false,
                'message' => 'Invalid phone number. Must be 10 digits.',
                'httpCode' => 400,
            ]);
        }

        $result = $this->businessModel->getBusiness($data);
        return $this->jsonResponse($response, $result);
    }

    public function createBusiness(Request $request, Response $response, array $args): Response
    {
        try {
            $contentType = $request->getHeaderLine('Content-Type');
            $payload = [];
            $files = [];

            if (stripos($contentType, 'multipart/form-data') !== false) {
                $payload = $request->getParsedBody() ?? [];
                $files = $request->getUploadedFiles() ?? [];
            } elseif (stripos($contentType, 'application/json') !== false) {
                $payload = json_decode((string)$request->getBody(), true) ?? [];
            } else {
                return $this->jsonResponse($response, [
                    'status' => false,
                    'message' => 'Unsupported Content-Type. Use JSON or multipart/form-data.',
                    'httpCode' => 415,
                ]);
            }

            foreach (['name', 'phone', 'addr1'] as $field) {
                if (empty($payload[$field])) {
                    return $this->jsonResponse($response, [
                        'status' => false,
                        'message' => "Missing required field: $field",
                        'httpCode' => 422,
                    ]);
                }
            }

            if (!preg_match('/^[a-zA-Z\s]{3,50}$/', $payload['name'])) {
                return $this->jsonResponse($response, [
                    'status' => false,
                    'message' => 'Invalid name. Only letters allowed (3–50).',
                    'httpCode' => 400,
                ]);
            }

            if (!preg_match('/^\d{10}$/', $payload['phone'])) {
                return $this->jsonResponse($response, [
                    'status' => false,
                    'message' => 'Invalid phone number. Must be 10 digits.',
                    'httpCode' => 400,
                ]);
            }

            if (!empty($payload['email']) && !filter_var($payload['email'], FILTER_VALIDATE_EMAIL)) {
                return $this->jsonResponse($response, [
                    'status' => false,
                    'message' => 'Invalid email address.',
                    'httpCode' => 400,
                ]);
            }

            $logoFile = $files['logo'] ?? null;
            if ($logoFile && $logoFile->getError() === UPLOAD_ERR_OK) {
                if ($logoFile->getSize() > 1 * 1024 * 1024) {
                    return $this->jsonResponse($response, [
                        'status' => false,
                        'message' => 'Logo too large (max 1MB).',
                        'httpCode' => 413,
                    ]);
                }

                $allowed = ['image/jpeg', 'image/png', 'image/webp'];
                if (!in_array($logoFile->getClientMediaType(), $allowed)) {
                    return $this->jsonResponse($response, [
                        'status' => false,
                        'message' => 'Invalid file type. JPG, PNG, WEBP only.',
                        'httpCode' => 415,
                    ]);
                }
            }

            $payloadFormatted = [
                'name'            => strtoupper($payload['name']),
                'phone'           => (int)$payload['phone'],
                'email'           => strtolower($payload['email'] ?? ''),
                'addr1'           => $payload['addr1'],
                'addr2'           => $payload['addr2'] ?? null,
                'businessLogoFile'=> $logoFile ?? null,
                'business_status' => 'active',
            ];

            $result = $this->businessModel->createBusiness($payloadFormatted);
            return $this->jsonResponse($response, $result);

        } catch (\Throwable $e) {
            return $this->jsonResponse($response, [
                'status' => false,
                'message' => 'Server Error: ' . $e->getMessage(),
                'httpCode' => 500,
            ]);
        }
    }

    public function updateBusiness(Request $request, Response $response, array $args): Response
    {
        try {
            $contentType = $request->getHeaderLine('Content-Type');
            $method = strtoupper($request->getMethod());
            $payload = [];
            $files = [];

            // === Handle PUT multipart manually ===
            if ($method === 'PUT' && stripos($contentType, 'multipart/form-data') !== false) {
                if (!preg_match('/boundary=(.*)$/', $contentType, $matches)) {
                    return $this->jsonResponse($response, [
                        'status' => false,
                        'message' => 'Invalid multipart/form-data (missing boundary)',
                        'httpCode' => 400,
                    ]);
                }

                $boundary = trim($matches[1]);
                $body = (string)$request->getBody();
                $parts = preg_split('/--' . preg_quote($boundary, '/') . '/', $body);

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

                            // Save info in consistent structure
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
            }
            // === Normal form-data via $_FILES ===
            elseif (stripos($contentType, 'multipart/form-data') !== false) {
                $payload = $request->getParsedBody() ?? [];
                $files = $request->getUploadedFiles() ?? [];
            }
            // === JSON ===
            elseif (stripos($contentType, 'application/json') !== false) {
                $payload = json_decode((string)$request->getBody(), true) ?? [];
            }
            else {
                return $this->jsonResponse($response, [
                    'status' => false,
                    'message' => 'Unsupported Content-Type.',
                    'httpCode' => 415,
                ]);
            }

            // === Validate businessId ===
            $businessId = $args['businessId'] ?? null;
            if (!$businessId) {
                return $this->jsonResponse($response, [
                    'status' => false,
                    'message' => 'Missing business ID.',
                    'httpCode' => 400,
                ]);
            }

            // === Validate required fields ===
            foreach (['name', 'phone', 'addr1'] as $field) {
                if (empty($payload[$field])) {
                    return $this->jsonResponse($response, [
                        'status' => false,
                        'message' => "Missing required field: $field",
                        'httpCode' => 422,
                    ]);
                }
            }

            // === Validate field formats ===
            if (!preg_match('/^[a-zA-Z\s]{3,50}$/', $payload['name'])) {
                return $this->jsonResponse($response, [
                    'status' => false,
                    'message' => 'Invalid name format (3–50 letters only).',
                    'httpCode' => 400,
                ]);
            }

            if (!preg_match('/^\d{10}$/', $payload['phone'])) {
                return $this->jsonResponse($response, [
                    'status' => false,
                    'message' => 'Invalid phone number (10 digits required).',
                    'httpCode' => 400,
                ]);
            }

            if (!empty($payload['email']) && !filter_var($payload['email'], FILTER_VALIDATE_EMAIL)) {
                return $this->jsonResponse($response, [
                    'status' => false,
                    'message' => 'Invalid email address.',
                    'httpCode' => 400,
                ]);
            }

            // === Prepare payload ===
            $payloadFormatted = [
                'name'   => strtoupper(trim($payload['name'])),
                'phone'  => (int) $payload['phone'],
                'email'  => strtolower($payload['email'] ?? ''),
                'addr1'  => trim($payload['addr1']),
                'addr2'  => $payload['addr2'] ?? null,
                'status' => 'active',
            ];

            // === Handle logo ===
            if (isset($files['logo'])) {
                $logoInfo = $files['logo'];

                // Manually parsed file (array)
                if (is_array($logoInfo) && isset($logoInfo['tmp_name'])) {
                    if ($logoInfo['size'] > 1024 * 1024) {
                        return $this->jsonResponse($response, [
                            'status' => false,
                            'message' => 'Logo too large (max 1MB).',
                            'httpCode' => 413,
                        ]);
                    }

                    $allowed = ['image/jpeg', 'image/png', 'image/webp'];
                    if (!in_array($logoInfo['type'], $allowed)) {
                        return $this->jsonResponse($response, [
                            'status' => false,
                            'message' => 'Invalid logo type (jpeg, png, webp only).',
                            'httpCode' => 415,
                        ]);
                    }

                    $payloadFormatted['businessLogo'] = $logoInfo;
                }
                // Slim UploadedFile
                elseif ($logoInfo instanceof \Slim\Psr7\UploadedFile) {
                    $payloadFormatted['businessLogo'] = $logoInfo;
                }
            }
            elseif (!empty($payload['logo'])) {
                $payloadFormatted['businessLogo'] = $payload['logo']; // base64 or path
            }

            // === Call model ===
            $result = $this->businessModel->updateBusiness($businessId, $payloadFormatted);
            return $this->jsonResponse($response, $result);

        } catch (\Throwable $e) {
            return $this->jsonResponse($response, [
                'status' => false,
                'message' => 'Server Error: ' . $e->getMessage(),
                'httpCode' => 500,
            ]);
        }
    }

    public function deleteBusiness(Request $request, Response $response, array $args): Response
    {
        try {
            $businessId = (string)($args['businessId'] ?? '');
            if (!$businessId) {
                return $this->jsonResponse($response, [
                    'status' => false,
                    'message' => 'Missing businessId in URL',
                    'httpCode' => 422,
                ]);
            }

            $result = $this->businessModel->deleteBusiness($businessId);
            return $this->jsonResponse($response, $result);
        } catch (\Throwable $e) {
            return $this->jsonResponse($response, [
                'status' => false,
                'message' => 'Server Error: ' . $e->getMessage(),
                'httpCode' => 500,
            ]);
        }
    }
    
    public function fetchDashboardStats(Request $request, Response $response, array $args): Response
    {
        $queryParams = $request->getQueryParams();

        $data = [
            'searchQuery' => $queryParams['q'] ?? null,
            'businessId'  => isset($queryParams['id']) ? strtoupper($queryParams['id']) : null,
            'phone'       => isset($queryParams['ph']) ? (int) $queryParams['ph'] : null,
            'status'      => $queryParams['sts'] ?? 'active',
            'fromDate'    => isset($queryParams['fromDate']) ? $queryParams['fromDate'] : null,
            'toDate'    => isset($queryParams['toDate']) ? $queryParams['toDate'] : null,
            'order'       => strtoupper($queryParams['ord'] ?? 'DESC'),
            'period'      => isset($queryParams['period']) ? strtolower($queryParams['period']) : 'today',
            'page'        => (int)($queryParams['page'] ?? 1),
            'limit'       => (int)($queryParams['limit'] ?? 25),
        ];

        if (!empty($data['phone']) && !preg_match('/^\d{10}$/', (string)$data['phone'])) {
            return $this->jsonResponse($response, [
                'status' => false,
                'message' => 'Invalid phone number. Must be 10 digits.',
                'httpCode' => 400,
            ]);
        }

        $result = $this->businessModel->fetchDashboardStats($data);
        return $this->jsonResponse($response, $result);
    }
    
    public function fetchActivityLogs(Request $request, Response $response, array $args): Response
    {
        $queryParams = $request->getQueryParams();

        $data = [
            'searchQuery' => $queryParams['q'] ?? null,
            'businessId'  => isset($queryParams['id']) ? strtoupper($queryParams['id']) : null,
            'status'      => $queryParams['sts'] ?? 'active',
            'order'       => strtoupper($queryParams['ord'] ?? 'DESC'),
            'page'        => (int)($queryParams['page'] ?? 1),
            'limit'       => (int)($queryParams['limit'] ?? 25),
        ];

        $result = $this->businessModel->fetchActivityLogs($data);
        return $this->jsonResponse($response, $result);
    }

    private function jsonResponse(Response $response, array $result): Response
    {
        $httpCode = $result['httpCode'] ?? 500;
        unset($result['httpCode']);
        $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($httpCode);
    }
}
