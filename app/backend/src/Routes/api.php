<?php
use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

// Helpers
use App\Helper\ApiHelper;
// Controllers
use App\Controller\UserController;
use App\Controller\AuthController;
use App\Controller\InvoiceController;

return function (App $app) {

    // Controllers
    $userController = new UserController();
    $authController = new AuthController();
    $invoiceController = new InvoiceController();

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

    // Public `/api/*` routes & JWT not required
    $app->group('/api', function ($group) use (
        $authController,
        $userController,
        $invoiceController
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
    });

};