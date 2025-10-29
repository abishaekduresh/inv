<?php
namespace App\Controller;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Model\UserModel;

class UserController
{
    private UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function getUser(Request $request, Response $response, array $args): Response 
    {
        $query = $request->getQueryParams();

        // Extract filters
        $searchQuery = $query['q'] ?? null;
        $userId = $query['id'] ?? null;

        $data = [
            'searchQuery' => $searchQuery,
            'userId'      => $userId,
            'status'      => $query['sts'] ?? 'active',
            'page'        => (int)($query['page'] ?? 1),
            'limit'       => (int)($query['limit'] ?? 25),
        ];

        $result = $this->userModel->getUser($data);

        return $this->jsonResponse($response, $result);
    }

    public function createUser(Request $request, Response $response, array $args): Response
    {
        try {
            $contentType = $request->getHeaderLine('Content-Type');
            $payload = [];

            // --- Parse payload ---
            if (stripos($contentType, 'application/json') !== false) {
                $payload = json_decode((string) $request->getBody(), true) ?? [];
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return $this->jsonResponse($response, [
                        'status'   => false,
                        'message'  => 'Invalid JSON payload',
                        'httpCode' => 400,
                    ]);
                }
            } elseif (stripos($contentType, 'multipart/form-data') !== false) {
                $payload = $request->getParsedBody() ?? [];
                $files = $request->getUploadedFiles() ?? [];
                $payload['files'] = $this->normalizeFiles($files);
            } else {
                return $this->jsonResponse($response, [
                    'status'   => false,
                    'message'  => 'Unsupported Content-Type. Use JSON or multipart/form-data',
                    'httpCode' => 415,
                ]);
            }

            // --- Required fields with data types ---
            $requiredFields = [
                'name'             => 'string',
                'phone'            => 'integer',
                'role'         => 'string',
                'password'         => 'string',
                'confirmPassword' => 'string',
            ];

            foreach ($requiredFields as $field => $type) {
                if (!isset($payload[$field])) {
                    return $this->jsonResponse($response, [
                        'status'   => false,
                        'message'  => "Missing required field: $field",
                        'httpCode' => 422,
                    ]);
                }

                // Type validation
                $value = $payload[$field];
                switch ($type) {
                    case 'string':
                        $isValid = is_string($value);
                        break;
                    case 'integer':
                        $isValid = is_int($value) || ctype_digit((string)$value);
                        break;
                    case 'float':
                        $isValid = is_float($value) || is_numeric($value);
                        break;
                    case 'boolean':
                        $isValid = is_bool($value) || in_array($value, ['0','1',0,1], true);
                        break;
                    default:
                        $isValid = true;
                        break;
                }

                if (!$isValid) {
                    return $this->jsonResponse($response, [
                        'status'   => false,
                        'message'  => "Invalid data type for field: $field. Expected $type.",
                        'httpCode' => 422,
                    ]);
                }
            }
    
            // Validate Name if provided
            if (!is_string($payload['name']) || !preg_match('/^[a-zA-Z\s]+$/', $payload['name']) || strlen($payload['name']) < 3) {
                $response->getBody()->write(json_encode([
                    'status' => false,
                    'message' => "Name should be at least 3 characters and contain only alphabetic characters.", 
                    'httpCode' => 400,
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
        
            // Validate mobile number
            if (!isset($payload['phone']) || !preg_match('/^\d{10}$/', $payload['phone'])) {
                return $this->jsonResponse($response, [
                    'status' => false,
                    'message' => 'Invalid mobile number!!!',
                    'httpCode' => 400,
                    'help'  => 'Phone number shoud be 10 digit'
                ]);
            }

            // --- Password confirmation check ---
            if (isset($payload['password']) && $payload['password'] !== $payload['confirmPassword']) {
                return $this->jsonResponse($response, [
                    'status'   => false,
                    'message'  => "Password and confirm password do not match",
                    'httpCode' => 422,
                ]);
            }

            $payloadFormatted = [
                'name' => isset($payload['name']) ? (string) strtoupper($payload['name']) : null,
                'phone' => isset($payload['phone']) ? (int) $payload['phone'] : null,
                'role' => isset($payload['role']) ? (string) strtolower($payload['role']) : null,
                'password' => isset($payload['password']) ? (string) $payload['password'] : null
            ];

            $result = $this->userModel->createUser($payloadFormatted);
            return $this->jsonResponse($response, $result);

        } catch (\Throwable $e) {
            return $this->jsonResponse($response, [
                'status'   => false,
                'message'  => 'Server Error: ' . $e->getMessage(),
                'httpCode' => 500,
            ]);
        }
    }

    public function updateUser(Request $request, Response $response, array $args): Response
    {
        try {
            $contentType = $request->getHeaderLine('Content-Type');
            $payload = [];

            // --- Parse payload ---
            if (stripos($contentType, 'application/json') !== false) {
                $payload = json_decode((string) $request->getBody(), true) ?? [];
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return $this->jsonResponse($response, [
                        'status'   => false,
                        'message'  => 'Invalid JSON payload',
                        'httpCode' => 400,
                    ]);
                }
            } elseif (stripos($contentType, 'multipart/form-data') !== false) {
                $payload = $request->getParsedBody() ?? [];
                $files = $request->getUploadedFiles() ?? [];
                $payload['files'] = $this->normalizeFiles($files);
            } else {
                return $this->jsonResponse($response, [
                    'status'   => false,
                    'message'  => 'Unsupported Content-Type. Use JSON or multipart/form-data',
                    'httpCode' => 415,
                ]);
            }

            // --- Get userId from URL args ---
            $userId = (string) ($args['userId'] ?? '');
            if (!$userId) {
                return $this->jsonResponse($response, [
                    'status'   => false,
                    'message'  => 'Missing userId in URL',
                    'httpCode' => 422,
                ]);
            }

            // --- Password confirmation check ---
            if (isset($payload['password'])) {
                // Require confirmPassword when updating password
                if (!isset($payload['confirmPassword']) || empty($payload['confirmPassword'])) {
                    return $this->jsonResponse($response, [
                        'status'   => false,
                        'message'  => 'Confirm password is required when updating password',
                        'httpCode' => 422,
                    ]);
                }

                // Passwords must match
                if ($payload['password'] !== $payload['confirmPassword']) {
                    return $this->jsonResponse($response, [
                        'status'   => false,
                        'message'  => 'Password and confirm password do not match',
                        'httpCode' => 422,
                    ]);
                }
            }

            // --- Required fields and validation ---
            $requiredFields = [
                'name'  => 'string',
                'phone' => 'integer',
            ];

            foreach ($requiredFields as $field => $type) {
                if (!isset($payload[$field])) {
                    return $this->jsonResponse($response, [
                        'status'   => false,
                        'message'  => "Missing required field: $field",
                        'httpCode' => 422,
                    ]);
                }

                $value = $payload[$field];
                switch ($type) {
                    case 'string':
                        $isValid = is_string($value);
                        break;
                    case 'integer':
                        $isValid = is_int($value) || ctype_digit((string)$value);
                        break;
                    case 'float':
                        $isValid = is_float($value) || is_numeric($value);
                        break;
                    case 'boolean':
                        $isValid = is_bool($value) || in_array($value, ['0','1',0,1], true);
                        break;
                    default:
                        $isValid = true;
                        break;
                }

                if (!$isValid) {
                    return $this->jsonResponse($response, [
                        'status'   => false,
                        'message'  => "Invalid data type for field: $field. Expected $type.",
                        'httpCode' => 422,
                    ]);
                }
            }

            $payloadFormatted = [
                'name' => isset($payload['name']) ? (string) strtoupper($payload['name']) : null,
                'phone' => isset($payload['phone']) ? (int) $payload['phone'] : null,
                'role' => isset($payload['role']) ? (string) strtolower($payload['role']) : null,
                'status' => isset($payload['status']) ? (string) strtolower($payload['status']) : null,
                'password' => isset($payload['password']) ? (string) $payload['password'] : null
            ];

            $result = $this->userModel->updateUser($userId, $payloadFormatted);
            return $this->jsonResponse($response, $result);

        } catch (\Throwable $e) {
            return $this->jsonResponse($response, [
                'status'   => false,
                'message'  => 'Server Error: ' . $e->getMessage(),
                'httpCode' => 500,
            ]);
        }
    }

    public function deleteUser(Request $request, Response $response, array $args): Response
    {
        try {
            $contentType = $request->getHeaderLine('Content-Type');
            $payload = [];

            // --- Get userId from URL args ---
            $userId = (string) ($args['userId'] ?? '');
            if (!$userId) {
                return $this->jsonResponse($response, [
                    'status'   => false,
                    'message'  => 'Missing userId in URL',
                    'httpCode' => 422,
                ]);
            }

            // --- Call model ---
            $result = $this->userModel->deleteUser($userId);

            return $this->jsonResponse($response, $result);

        } catch (\Throwable $e) {
            return $this->jsonResponse($response, [
                'status'   => false,
                'message'  => 'Server Error: ' . $e->getMessage(),
                'httpCode' => 500,
            ]);
        }
    }

    /**
     * Normalize uploaded files to simple array
     */
    private function normalizeFiles(array $files): array
    {
        $result = [];
        foreach ($files as $key => $file) {
            if (is_array($file)) {
                $result[$key] = $this->normalizeFiles($file);
            } else {
                $result[$key] = [
                    'filename' => $file->getClientFilename(),
                    'size'     => $file->getSize(),
                    'type'     => $file->getClientMediaType(),
                    'error'    => $file->getError(),
                ];
            }
        }
        return $result;
    }

    private function jsonResponse(Response $response, array $result): Response
    {
        $httpCode = $result['httpCode'] ?? 500;
        unset($result['httpCode']);
        $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($httpCode);
    }
}
