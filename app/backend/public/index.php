<?php
ob_start(); // Buffer output

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpMethodNotAllowedException;

// --------------------
// PACKAGES
// --------------------
use Dotenv\Dotenv;
// --------------------
// MIDDLEWARES
// --------------------
use App\Middleware\EnvValidationMiddleware;
// --------------------
// HELPERS
// --------------------
use App\Helper\ErrorLogger;
// --------------------
// LOAD ENVIRONMENT
// --------------------
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

// --------------------
// LOGGER SETUP
// --------------------
$logDir = __DIR__ . '/../logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0777, true);
}
$errorHelper = new ErrorLogger($logDir, 'error.log');

// --------------------
// SLIM APP INITIALIZATION
// --------------------
$app = AppFactory::create();
$app->addBodyParsingMiddleware(); // For JSON & multipart/form-data

// --------------------
// REQUIRED ENV VARIABLES VALIDATION
// --------------------
$requiredEnv = [
    'APP_ENV',
    'BASE_API_PATH',
    'BACKEND_API_URL'
];

if (($_ENV['APP_ENV'] ?? 'production') === 'production') {
    $requiredEnv[] = 'DB_PASS';
}

$app->add(new EnvValidationMiddleware($requiredEnv));

// --------------------
// ROUTES
// --------------------
(require __DIR__ . '/../src/Routes/api.php')($app);

// --------------------
// CUSTOM 404 HANDLER
// --------------------
$customNotFoundHandler = function (Request $request, HttpNotFoundException $exception, bool $displayErrorDetails) {
    $response = new \Slim\Psr7\Response();
    // Get requested URL
    $requestedUrl = (string)$request->getUri();
    $response->getBody()->write(json_encode([
        'status'  => false,
        'message' => 'The requested route was not found on the server.',
        'requestedUrl' => $requestedUrl,  // include the URL
        'meta'    => [
            'line' => $exception->getLine(),
            'file' => $exception->getFile(),
        ]
    ]));

    return $response->withHeader('Content-Type', 'application/json')
                    ->withStatus(404);
};

// --------------------
// ERROR MIDDLEWARE
// --------------------
$errorMiddleware = $app->addErrorMiddleware(true, true, true);

// Default error handler
$errorMiddleware->setDefaultErrorHandler(function (
    Request $request,
    Throwable $exception,
    bool $displayErrorDetails,
    bool $logErrors,
    bool $logErrorDetails
) use ($errorHelper) {
    $errorHelper->handleException($exception);

    $response = new \Slim\Psr7\Response();
    $response->getBody()->write(json_encode([
        'status'  => false,
        'message' => 'An internal server error occurred.',
        'meta'    => [
            'error' => $exception->getMessage(),
            'line'  => $exception->getLine(),
            'file'  => $exception->getFile()
        ]
    ]));

    return $response->withHeader('Content-Type', 'application/json')
                    ->withStatus(500);
});

// Method Not Allowed handler
$customMethodNotAllowedHandler = function (Request $request, HttpMethodNotAllowedException $exception, bool $displayErrorDetails) {
    $response = new \Slim\Psr7\Response();
    $response->getBody()->write(json_encode([
        'status'  => false,
        'message' => 'Method not allowed.'
    ]));

    return $response->withHeader('Content-Type', 'application/json')
                    ->withStatus(405);
};

$errorMiddleware->setErrorHandler(HttpMethodNotAllowedException::class, $customMethodNotAllowedHandler);
$errorMiddleware->setErrorHandler(HttpNotFoundException::class, $customNotFoundHandler);

// --------------------
// RUN APPLICATION
// --------------------
$app->run();
ob_end_flush();
