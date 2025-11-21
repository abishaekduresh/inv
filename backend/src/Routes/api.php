<?php

use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

// Middleware
use App\Middleware\JwtMiddleware;
use App\Middleware\ActivityLoggerMiddleware;

// Helpers
use App\Helper\TimestampHelper;
use App\Helper\JwtHelper;
use App\Helper\LoggerHelper;

// Controllers
use App\Controller\UserController;
use App\Controller\AuthController;
use App\Controller\InvoiceController;
use App\Controller\BusinessController;

return function (App $app) {

    // Initialize helpers
    $timestampHelper = new TimestampHelper();
    $jwtHelper = new JwtHelper(
        $_ENV['JWT_SECRET'],
        $_ENV['JWT_ALGO'],
        $_ENV['JWT_EXPIRY']
    );

    // Initialize controllers
    $userController     = new UserController();
    $authController     = new AuthController();
    $invoiceController  = new InvoiceController();
    $businessController = new BusinessController();

    // 🔹 Set Base Path
    $app->setBasePath($_ENV['BASE_API_PATH']);

    /**
     * ------------------------------------------------------------
     * Test Route (No JWT required)
     * ------------------------------------------------------------
     */
    $app->get('/api/test', function (Request $request, Response $response) use ($timestampHelper) {
        $payload = [
            'status'    => true,
            'message'   => 'Test route success',
            'timestamp' => $timestampHelper->getCurrentTimestamp(),
        ];

        $response->getBody()->write(json_encode($payload));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    });

    /**
     * ------------------------------------------------------------
     * Public Routes (/api/*)
     * ------------------------------------------------------------
     */
    $app->group('/api', function ($group) use ($authController, $invoiceController, $businessController) {
        $group->post('/auth/users/login', [$authController, 'loginUser'])->setName('Users logged in');
        $group->get('/shared/invoices/{invoiceId}', [$invoiceController, 'getSharedInvoice']);
        // $group->get('/business/stats', [$businessController, 'fetchDashboardStats']);
    })->add(new ActivityLoggerMiddleware());

    /**
     * ------------------------------------------------------------
     * File Serve Route
     * ------------------------------------------------------------
     */
    $app->get('/uploads/{path:.*}', function ($request, $response, $args) {
        $filePath = __DIR__ . '/../../uploads/' . $args['path'];

        if (!file_exists($filePath)) {
            $response->getBody()->write('File not found');
            return $response->withHeader('Content-Type', 'application/json');
        }

        $mime = mime_content_type($filePath);
        $stream = new \Slim\Psr7\Stream(fopen($filePath, 'rb'));

        return $response
            ->withHeader('Content-Type', $mime)
            ->withBody($stream);
    });

    /**
     * ------------------------------------------------------------
     * Protected Routes (/api/*)
     * ------------------------------------------------------------
     */
    $app->group('/api', function ($group) use ($userController, $invoiceController, $businessController) {

        // User routes
        $group->get('/users', [$userController, 'getUser'])->setName('Users retrived Updated');
        $group->post('/users', [$userController, 'createUser']);
        $group->put('/users/{userId}', [$userController, 'updateUser']);
        $group->delete('/users/{userId}', [$userController, 'deleteUser']);

        // Invoice routes
        $group->get('/invoices', [$invoiceController, 'getInvoice']);
        $group->post('/invoices', [$invoiceController, 'createInvoice']);
        $group->put('/invoices/{invoiceId}', [$invoiceController, 'updateInvoice']);
        $group->delete('/invoices/{invoiceId}', [$invoiceController, 'deleteInvoice']);

        // Business routes
        $group->get('/business', [$businessController, 'getBusiness']);
        $group->post('/business', [$businessController, 'createBusiness']);
        $group->put('/business/{businessId}', [$businessController, 'updateBusiness']);
        $group->delete('/business/{businessId}', [$businessController, 'deleteBusiness']);
        // Dashboard
        $group->get('/business/stats', [$businessController, 'fetchDashboardStats']);
        // Activity Log
        $group->get('/business/activity/logs', [$businessController, 'fetchActivityLogs']);
    })
    // Correct order: First JWT (auth), then Logger (logs request)
    ->add(new ActivityLoggerMiddleware())
    ->add(new JwtMiddleware($jwtHelper));

};
