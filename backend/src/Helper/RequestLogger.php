<?php
namespace App\Helper;

use Psr\Http\Message\ServerRequestInterface as Request;

class RequestLogger
{
    private string $logFile;
    private string $uploadDir;

    public function __construct(string $logDir = __DIR__ . '/../../logs', string $fileName = 'requests.log', string $uploadDir = __DIR__ . '/../../uploads')
    {
        if (!is_dir($logDir)) mkdir($logDir, 0777, true);
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $this->logFile = $logDir . '/' . $fileName;
        $this->uploadDir = $uploadDir;
    }

    public function log(Request $request): void
    {
        $headers = $request->getHeaders();
        $contentType = strtolower($request->getHeaderLine('Content-Type') ?? '');
        $rawBody = (string) $request->getBody();

        // Parsed body
        $parsedBody = $request->getParsedBody() ?? [];

        // Uploaded files info
        $files = [];
        foreach ($request->getUploadedFiles() as $name => $file) {
            $files[$name] = [
                'filename' => $file->getClientFilename(),
                'size'     => $file->getSize(),
                'type'     => $file->getClientMediaType(),
            ];
        }

        // Handle JSON body if Content-Type is JSON
        if (strpos($contentType, 'application/json') !== false && !empty($rawBody)) {
            $parsedBody = json_decode($rawBody, true) ?? $parsedBody;
        }

        $entry = [
            'time'        => date('Y-m-d H:i:s'),
            'method'      => $request->getMethod(),
            'uri'         => (string)$request->getUri(),
            'contentType' => $contentType,
            'headers'     => $headers,
            'rawBody'     => $rawBody,
            'parsedBody'  => $parsedBody,
            'files'       => $files
        ];

        file_put_contents($this->logFile, json_encode($entry, JSON_PRETTY_PRINT) . PHP_EOL, FILE_APPEND);
    }
}
