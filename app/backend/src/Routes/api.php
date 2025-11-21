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
        // Dashboard
        $group->get('/business/stats', [$businessController, 'fetchDashboardStats']);
        // Activity Log
        $group->get('/business/activity/logs', [$businessController, 'fetchActivityLogs']);
        // Shared
        $group->get('/shared/invoices/{invoiceId}', [$invoiceController, 'getSharedInvoice']);
    });

};