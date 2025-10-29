<?php
namespace App\Controller;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Model\AuthModel;

class AuthController
{
    private authModel $authModel;

    public function __construct()
    {
        $this->authModel = new authModel();
    }

    public function loginUser(Request $request, Response $response, array $args): Response
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

            // --- Decode inner JSON if exists ---
            $decoded = $payload;
            if (!empty($payload['event_log'])) {
                $decoded = json_decode($payload['event_log'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return $this->jsonResponse($response, [
                        'status'   => false,
                        'message'  => 'Invalid JSON in event_log',
                        'httpCode' => 400,
                    ]);
                }
            }

            // --- Required fields with data types ---
            $requiredFields = [
                'phone'            => 'integer',
                'password'         => 'string',
            ];

            foreach ($requiredFields as $field => $type) {
                if (!isset($decoded[$field])) {
                    return $this->jsonResponse($response, [
                        'status'   => false,
                        'message'  => "Missing required field: $field",
                        'httpCode' => 422,
                    ]);
                }

                // Type validation
                $value = $decoded[$field];
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

            $result = $this->authModel->loginUser($decoded);

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

        // Extract and remove token before encoding
        $jwtToken = $result['jwtToken'] ?? null;
        unset($result['httpCode'], $result['jwtToken']);

        // Write JSON body (without token)
        $response->getBody()->write(json_encode(
            $result,
            JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
        ));

        // Always JSON
        $response = $response->withHeader('Content-Type', 'application/json');

        // ✅ Add JWT token to header if present
        if (!empty($jwtToken)) {
            $response = $response->withHeader("Authorization", "Bearer " . $jwtToken);
        }

        return $response->withStatus($httpCode);
    }

}
