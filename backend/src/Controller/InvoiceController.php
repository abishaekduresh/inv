<?php
namespace App\Controller;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Model\InvoiceModel;

class InvoiceController
{
    private InvoiceModel $invoiceModel;

    public function __construct()
    {
        $this->invoiceModel = new invoiceModel();
    }

    public function genInvoiceId(Request $request, Response $response, array $args): Response
    {
        try {
            $payloadFormatted = null;

            $result = $this->invoiceModel->genInvoiceId($payloadFormatted);
            return $this->jsonResponse($response, $result);

        } catch (\Throwable $e) {
            return $this->jsonResponse($response, [
                'status'   => false,
                'message'  => 'Server Error: ' . $e->getMessage(),
                'httpCode' => 500,
            ]);
        }
    }

    public function getInvoice(Request $request, Response $response, array $args): Response 
    {
        $queryParams = $request->getQueryParams();

        // Extract filters
        $data = [
            'searchQuery'       => isset($queryParams['q']) ? (string) $queryParams['q'] : null,
            'invoiceId'         => isset($queryParams['id']) ? (string) strtoupper($queryParams['id']) : null,
            'phone'             => isset($queryParams['ph']) ? (int) $queryParams['ph'] : null,
            'invocieNumber'     => isset($queryParams['invno']) ? (int) $queryParams['invno'] : null,
            'place'             => isset($queryParams['pl']) ? (string) $queryParams['pl'] : null,
            'invoiceType'       => isset($queryParams['invtype']) ? (string) $queryParams['invtype'] : null,
            'fromDate'          => !empty($queryParams['fmd']) ? date('Y-m-d', strtotime($queryParams['fmd'])) : null,
            'toDate'            => !empty($queryParams['tod']) ? date('Y-m-d', strtotime($queryParams['tod'])) : null,
            'dob'               => !empty($queryParams['dob']) ? date('Y-m-d', strtotime($queryParams['dob'])) : null,
            'status'            => isset($queryParams['sts']) ? (string) strtolower($queryParams['sts']) : null,
            'order'             => isset($queryParams['ord']) ? strtoupper($queryParams['ord']) : 'DESC',
            'page'              => (int)($queryParams['page'] ?? 1),
            'limit'             => (int)($queryParams['limit'] ?? 25),
        ];
        
        // Validate phone number
        if (isset($data['phone']) && !empty($data['phone']) && !preg_match('/^\d{10}$/', $data['phone'])) {
            return $this->jsonResponse($response, [
                'status' => false,
                'message' => 'Invalid phone number!!!',
                'httpCode' => 400,
                'help'  => 'Phone number shoud be 10 digit'
            ]);
        }

        if(empty($data['fromDate']) && !empty($data['toDate'])) {
            return $this->jsonResponse($response, [
                'status'    => false,
                'http_code' => 400,
                'message'   => "'fromDate' and 'toDate' must both be provided."
            ]);
        }

        if(!empty($data['fromDate']) && !empty($data['toDate']) && $data['fromDate'] > $data['toDate']) {
            return $this->jsonResponse($response, [
                'status'    => false,
                'http_code' => 400,
                'message'   => "'fromDate' cannot be later than 'toDate'."
            ]);
        }

        $result = $this->invoiceModel->getInvoice($data);

        return $this->jsonResponse($response, $result);
    }

    public function createInvoice(Request $request, Response $response, array $args): Response
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

            // --- Required fields ---
            $requiredFields = [
                'invoiceType'   => 'string',
                'name'   => 'string',
                'phone' => 'integer',
                'invoiceNumber' => 'integer',
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

            // Validate name
            if (!preg_match('/^[a-zA-Z\s]{3,50}$/', $payload['name'])) {
                return $this->jsonResponse($response, [
                    'status'   => false,
                    'message'  => "Name should be 3-50 characters, alphabetic only.",
                    'httpCode' => 400,
                ]);
            }

            // Validate name
            if (empty($payload['invoiceType'])) {
                return $this->jsonResponse($response, [
                    'status'   => false,
                    'message'  => "Invoice type is required.",
                    'httpCode' => 400,
                ]);
            }

            // Validate phone number
            if (!preg_match('/^\d{10}$/', $payload['phone'])) {
                return $this->jsonResponse($response, [
                    'status'   => false,
                    'message'  => 'Invalid phone number. Must be 10 digits.',
                    'httpCode' => 400,
                ]);
            }

            // Validate amount (integer or float)
            if (!preg_match('/^\d+(\.\d+)?$/', $payload['amount'])) {
                return $this->jsonResponse($response, [
                    'status'   => false,
                    'message'  => 'Invalid amount value. Must be a number (integer or decimal).',
                    'httpCode' => 400,
                ]);
            }

            // --- Format payload for DB ---
            $payloadFormatted = [
                'invoiceType'  => isset($payload['invoiceType']) ? (string) strtolower($payload['invoiceType']) : null,
                'invoiceNumber' => (int) $payload['invoiceNumber'],
                'invoiceDate'  => isset($payload['invoiceDate']) ? $payload['invoiceDate'] : null,
                'name' => strtoupper($payload['name']),
                'phone' => (int) $payload['phone'],
                'dob' => !empty($payload['dob']) ? date('Y-m-d', strtotime($payload['dob'])) : null,
                'place' => $payload['place'] ?? null,
                'frame' => $payload['frame'] ?? null,
                'lence' => $payload['lence'] ?? null,
                'rSph' => $payload['rSph'] ?? null,
                'rCyl' => $payload['rCyl'] ?? null,
                'rAxis' => $payload['rAxis'] ?? null,
                'rVia' => $payload['rVia'] ?? null,
                'rAdd' => $payload['rAdd'] ?? null,
                'rPd' => $payload['rPd'] ?? null,
                'lSph' => $payload['lSph'] ?? null,
                'lCyl' => $payload['lCyl'] ?? null,
                'lAxis' => $payload['lAxis'] ?? null,
                'lVia' => $payload['lVia'] ?? null,
                'lAdd' => $payload['lAdd'] ?? null,
                'lPd' => $payload['lPd'] ?? null,
                'amount' => !empty($payload['amount']) ? (float) $payload['amount'] : 0.00,
                'offer' => $payload['offer'] ?? null,
                'claim' => $payload['claim'] ?? null,
                'remark' => $payload['remark'] ?? null,
                'paymentMode' => $payload['paymentMode'] ?? null,
                'invoiceStatus' => 'active',
            ];

            $result = $this->invoiceModel->createInvoice($payloadFormatted);
            return $this->jsonResponse($response, $result);

        } catch (\Throwable $e) {
            return $this->jsonResponse($response, [
                'status'   => false,
                'message'  => 'Server Error: ' . $e->getMessage(),
                'httpCode' => 500,
            ]);
        }
    }

    public function updateInvoice(Request $request, Response $response, array $args): Response
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

            // --- Get invoiceId from URL args ---
            $invoiceId = (string) ($args['invoiceId'] ?? '');
            if (!$invoiceId) {
                return $this->jsonResponse($response, [
                    'status'   => false,
                    'message'  => 'Missing invoiceId in URL',
                    'httpCode' => 422,
                ]);
            }

            // --- Required fields validation ---
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
                    default:
                        $isValid = true;
                }

                if (!$isValid) {
                    return $this->jsonResponse($response, [
                        'status'   => false,
                        'message'  => "Invalid data type for field: $field. Expected $type.",
                        'httpCode' => 422,
                    ]);
                }
            }

            // Validate name
            if (!preg_match('/^[a-zA-Z\s]{3,50}$/', $payload['name'])) {
                return $this->jsonResponse($response, [
                    'status'   => false,
                    'message'  => "Name should be 3-50 characters, alphabetic only.",
                    'httpCode' => 400,
                ]);
            }

            // Validate invoice type
            if (empty($payload['invoiceType'])) {
                return $this->jsonResponse($response, [
                    'status'   => false,
                    'message'  => "Invoice type is required.",
                    'httpCode' => 400,
                ]);
            }

            // Validate invoice status
            if (empty($payload['invoiceStatus'])) {
                return $this->jsonResponse($response, [
                    'status'   => false,
                    'message'  => "Invoice status is required.",
                    'httpCode' => 400,
                ]);
            }

            // Validate phone number
            if (!preg_match('/^\d{10}$/', $payload['phone'])) {
                return $this->jsonResponse($response, [
                    'status'   => false,
                    'message'  => 'Invalid phone number. Must be 10 digits.',
                    'httpCode' => 400,
                ]);
            }

            // Validate amount (integer or float)
            if (!preg_match('/^\d+(\.\d+)?$/', $payload['amount'])) {
                return $this->jsonResponse($response, [
                    'status'   => false,
                    'message'  => 'Invalid amount value. Must be a number (integer or decimal).',
                    'httpCode' => 400,
                ]);
            }

            // --- Format payload for DB ---
            $payloadFormatted = [
                'invoiceType'  => isset($payload['invoiceType']) ? (string) strtolower($payload['invoiceType']) : null,
                'invoiceNumber' => isset($payload['invoiceNumber']) ? (int)$payload['invoiceNumber'] : null,
                'invoiceDate'   => isset($payload['invoiceDate']) ? date('Y-m-d', strtotime($payload['invoiceDate'])) : null,
                'name'          => strtoupper($payload['name']),
                'phone'         => (int)$payload['phone'],
                'dob'           => !empty($payload['dob']) ? date('Y-m-d', strtotime($payload['dob'])) : null,
                'place'         => $payload['place'] ?? null,
                'frame'         => $payload['frame'] ?? null,
                'lence'         => $payload['lence'] ?? null,
                'rSph'         => $payload['rSph'] ?? null,
                'rCyl'          => $payload['rCyl'] ?? null,
                'rAxis'         => $payload['rAxis'] ?? null,
                'rVia'          => $payload['rVia'] ?? null,
                'rAdd'          => $payload['rAdd'] ?? null,
                'rPd'           => $payload['rPd'] ?? null,
                'lSph'         => $payload['lSph'] ?? null,
                'lCyl'          => $payload['lCyl'] ?? null,
                'lAxis'         => $payload['lAxis'] ?? null,
                'lVia'          => $payload['lVia'] ?? null,
                'lAdd'          => $payload['lAdd'] ?? null,
                'lPd'           => $payload['lPd'] ?? null,
                'amount'        => !empty($payload['amount']) ? (float)$payload['amount'] : 0.00,
                'offer'         => $payload['offer'] ?? null,
                'claim'         => $payload['claim'] ?? null,
                'remark'        => $payload['remark'] ?? null,
                'paymentMode'   => $payload['paymentMode'] ?? null,
                'invoiceStatus' => isset($payload['invoiceStatus']) ? strtolower($payload['invoiceStatus']) : 'active',
            ];

            $result = $this->invoiceModel->updateInvoice($invoiceId, $payloadFormatted);
            return $this->jsonResponse($response, $result);

        } catch (\Throwable $e) {
            return $this->jsonResponse($response, [
                'status'   => false,
                'message'  => 'Server Error: ' . $e->getMessage(),
                'httpCode' => 500,
            ]);
        }
    }

    public function deleteInvoice(Request $request, Response $response, array $args): Response
    {
        try {
            $contentType = $request->getHeaderLine('Content-Type');
            $payload = [];

            // --- Get invoiceId from URL args ---
            $invoiceId = (string) ($args['invoiceId'] ?? '');
            if (!$invoiceId) {
                return $this->jsonResponse($response, [
                    'status'   => false,
                    'message'  => 'Missing invoiceId in URL',
                    'httpCode' => 422,
                ]);
            }

            $result = $this->invoiceModel->deleteInvoice($invoiceId);
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
