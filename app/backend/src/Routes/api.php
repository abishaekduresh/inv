<?php
use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Psr7\Stream;
use GuzzleHttp\Client;

// Helpers
use App\Helper\ApiHelper;
// Controllers
use App\Controller\UserController;
use App\Controller\AuthController;
use App\Controller\InvoiceController;
use App\Controller\BusinessController;
use App\Controller\CommonController;

return function (App $app) {

    // Controllers
    $userController = new UserController();
    $authController = new AuthController();
    $invoiceController = new InvoiceController();
    $businessController = new BusinessController();
    $commonController = new CommonController();

    // 🔹 Set Base Path
    $app->setBasePath($_ENV['BASE_API_PATH']);

    // 🔹 Test Route
    $app->get('/api/test', function (Request $request, Response $response) {
        $payload = [
            'status'    => true,
            'message'   => 'Test route sccuess',
            'note'      => 'Frontend - Backend'
        ];

        $response->getBody()->write(json_encode($payload));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200); // explicitly set HTTP status
    });
    // No JWT required for image proxy
    $app->get('/uploads/{path:.*}', [$commonController, 'getUploads']);

    // $app->get('/uploads/{path:.*}', function ($request, $response, $args) {
    //     $path = $args['path'];
    //     $queryParams = $request->getQueryParams();
    //     $wantBase64 = isset($queryParams['base64']);

    //     // Core backend base URL — adjust if port/path differs
    //     $coreBackendBaseUrl = rtrim($_ENV['BACKEND_API_URL'], '/');
    //     $coreUrl = $coreBackendBaseUrl . '/uploads/' . ltrim($path, '/');

    //     $client = new Client([
    //         'timeout' => 15.0,
    //         'http_errors' => false, // prevent exceptions for 4xx/5xx
    //         'verify' => ($_ENV['APP_ENV'] ?? 'development') === 'production',
    //     ]);

    //     try {
    //         // Fetch the file from core backend
    //         $res = $client->get($coreUrl, ['stream' => true]);

    //         $statusCode = $res->getStatusCode();
    //         if ($statusCode !== 200) {
    //             $response->getBody()->write("Core backend returned status {$statusCode}");
    //             return $response->withStatus($statusCode);
    //         }

    //         $mime = $res->getHeaderLine('Content-Type') ?: 'application/octet-stream';
    //         $fileData = $res->getBody()->getContents();

    //         // Base64 version
    //         if ($wantBase64) {
    //             $base64 = 'data:' . $mime . ';base64,' . base64_encode($fileData);
    //             $response->getBody()->write($base64);
    //             return $response
    //                 ->withHeader('Content-Type', 'text/plain')
    //                 ->withHeader('Cache-Control', 'public, max-age=86400');
    //         }

    //         // Stream version (default)
    //         $stream = new Stream(fopen('php://temp', 'r+'));
    //         $stream->write($fileData);
    //         $stream->rewind();

    //         return $response
    //             ->withHeader('Content-Type', $mime)
    //             ->withHeader('Cache-Control', 'public, max-age=86400')
    //             ->withBody($stream);

    //     } catch (\Throwable $e) {
    //         $response->getBody()->write('Error fetching from core backend: ' . $e->getMessage());
    //         return $response->withStatus(500);
    //     }
    // });

    // Public `/api/*` routes & JWT not required
    $app->group('/api', function ($group) use (
        $authController,
        $userController,
        $invoiceController,
        $businessController
    ) {
        $group->post('/auth/users/login', [$authController, 'loginUser']);
        $group->post('/auth/logout', [$authController, 'logout']);
        $group->get('/users', [$userController, 'getUser']);
        $group->post('/users', [$userController, 'createUser']);
        $group->put('/users/{userId}', [$userController, 'updateUser']);
        $group->delete('/users/{userId}', [$userController, 'deleteUser']);
        // Invoice
        $group->get('/invoices', [$invoiceController, 'getInvoice']);
        $group->post('/invoices', [$invoiceController, 'createInvoice']);
        $group->put('/invoices/{invoiceId}', [$invoiceController, 'updateInvoice']);
        $group->delete('/invoices/{invoiceId}', [$invoiceController, 'deleteInvoice']);
        // Business
        $group->get('/business', [$businessController, 'getBusiness']);
        $group->post('/business', [$businessController, 'createBusiness']);
        $group->put('/business/{businessId}', [$businessController, 'updateBusiness']);
        $group->delete('/business/{businessId}', [$businessController, 'deleteBusiness']);
        // Shared
        $group->get('/shared/invoices', [$invoiceController, 'getSharedInvoice']);
    });

};