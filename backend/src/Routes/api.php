<?php

use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

// Middleware
use App\Middleware\JwtMiddleware;
use App\Middleware\ReqResLoggerMiddleware;

// Helpers
use App\Helper\TimestampHelper;
use App\Helper\JwtHelper;

// Controllers
use App\Controller\UserController;
use App\Controller\AuthController;
use App\Controller\InvoiceController;

return function (App $app) {

    // Initialize helpers
    $timestampHelper = new TimestampHelper();
    $jwtHelper = new JwtHelper(
        $_ENV['JWT_SECRET'],
        $_ENV['JWT_ALGO'],
        $_ENV['JWT_EXPIRY']
    );

    // Initialize controllers
    $userController    = new UserController();
    $authController    = new AuthController();
    $invoiceController = new InvoiceController();

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
    })->add(new ReqResLoggerMiddleware());

    /**
     * ------------------------------------------------------------
     * Public Routes (/api/*)
     * ------------------------------------------------------------
     */
    $app->group('/api', function ($group) use ($authController) {
        $group->post('/auth/users/login', [$authController, 'loginUser']);

        // Example protected profile route (optional)
        /*
        $group->get('/profile', function (Request $request, Response $response) {
            $users = $request->getAttribute('jwt');
            $response->getBody()->write(json_encode([
                'status' => true,
                'data'   => $users,
            ]));

            return $response->withHeader('Content-Type', 'application/json');
        });
        */
    });

    /**
     * ------------------------------------------------------------
     * Protected Routes (/api/*)
     * ------------------------------------------------------------
     */
    $app->group('/api', function ($group) use ($userController, $invoiceController) {
        // User routes
        $group->get('/users', [$userController, 'getUser']);
        $group->post('/users', [$userController, 'createUser']);
        $group->put('/users/{userId}', [$userController, 'updateUser']);
        $group->delete('/users/{userId}', [$userController, 'deleteUser']);

        // Invoice routes
        $group->get('/geninvoice', [$invoiceController, 'genInvoiceId']);
        $group->get('/invoices', [$invoiceController, 'getInvoice']);
        $group->post('/invoices', [$invoiceController, 'createInvoice']);
        $group->put('/invoices/{invoiceId}', [$invoiceController, 'updateInvoice']);
        $group->delete('/invoices/{invoiceId}', [$invoiceController, 'deleteInvoice']);
    })
    // 🔹 Order matters: JwtMiddleware runs first, then ReqResLoggerMiddleware
    ->add(new ReqResLoggerMiddleware())
    ->add(new JwtMiddleware($jwtHelper));
};
