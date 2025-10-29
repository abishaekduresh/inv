<?php
namespace App\Helper;

class ErrorLogger
{
    private string $logFile;

    public function __construct(string $logDir = __DIR__ . '/../../logs', string $fileName = 'error.log')
    {
        if (!is_dir($logDir)) mkdir($logDir, 0777, true);
        $this->logFile = $logDir . '/' . $fileName;

        // Enable error logging
        ini_set('display_errors', 1);           // optional, display errors
        ini_set('display_startup_errors', 1);
        ini_set('log_errors', 1);
        ini_set('error_log', $this->logFile);
        error_reporting(E_ALL);

        // Register handlers
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
    }

    // Handle PHP warnings, notices, etc.
    public function handleError($severity, $message, $file, $line): bool
    {
        $this->log([
            'type'     => 'PHP Error',
            'severity' => $severity,
            'message'  => $message,
            'file'     => $file,
            'line'     => $line,
        ]);
        return false; // continue normal PHP error handling
    }

    // Handle uncaught exceptions
    public function handleException($exception)
    {
        $this->log([
            'type'     => 'Uncaught Exception',
            'message'  => $exception->getMessage(),
            'file'     => $exception->getFile(),
            'line'     => $exception->getLine(),
            'trace'    => $exception->getTraceAsString(),
        ]);
    }

    // Handle fatal errors on shutdown
    public function handleShutdown()
    {
        $error = error_get_last();
        if ($error) {
            $this->log([
                'type'    => 'Shutdown Error',
                'message' => $error['message'],
                'file'    => $error['file'],
                'line'    => $error['line'],
            ]);
        }
    }

    // Core logging function
    private function log(array $data)
    {
        $entry = [
            'time' => date('Y-m-d H:i:s'),
            'data' => $data
        ];
        file_put_contents($this->logFile, json_encode($entry, JSON_PRETTY_PRINT) . PHP_EOL, FILE_APPEND);
    }
}
