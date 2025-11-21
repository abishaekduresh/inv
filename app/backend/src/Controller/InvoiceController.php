<?php
namespace App\Controller;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Helper\ApiHelper;

class InvoiceController
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

    public function getInvoice(Request $request, Response $response, array $args): Response
    {
        try {
            $payload = [];
            $payload = $queryParams = $request->getQueryParams();

            // Call backend API
            $apiUrl = rtrim($this->backendApiUrl, '/') . '/api/invoices';
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

    public function createInvoice(Request $request, Response $response, array $args): Response
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
            $apiUrl = rtrim($this->backendApiUrl, '/') . '/api/invoices';
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

    public function updateInvoice(Request $request, Response $response, array $args): Response
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
            $apiUrl = rtrim($this->backendApiUrl, '/') . '/api/invoices/' . $args['invoiceId'];
            $result = $this->apiHelper->request($apiUrl, 'PUT', $payload);

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

    public function deleteInvoice(Request $request, Response $response, array $args): Response
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
            $apiUrl = rtrim($this->backendApiUrl, '/') . '/api/invoices/' . $args['invoiceId'];
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

    public function getSharedInvoice(Request $request, Response $response, array $args): Response
    {
        try {
            $payload = [];
            $queryParams = $request->getQueryParams();
            $invoiceId = $queryParams['id'] ?? $args['invoiceId'] ?? null;

            // Call backend API
            $apiUrl = rtrim($this->backendApiUrl, '/') . '/api/shared/invoices/' . $invoiceId;
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
