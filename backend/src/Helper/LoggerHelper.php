<?php
namespace App\Helper;

use PDO;
use PDOException;

class LoggerHelper
{
    protected string $table = 'activity_logs';
    private DatabaseHelper $pdoHelper;
    private TimestampHelper $timestampHelper;

    public function __construct()
    {
        // Properly define and assign class properties
        $this->pdoHelper       = new DatabaseHelper();
        $this->timestampHelper = new TimestampHelper();
    }

    public function log(array $data): bool
    {
        try {
            // Get shared PDO connection
            $conn = $this->pdoHelper::getConnection();

            // Use readable timestamp instead of raw UNIX seconds
            $currentTimestamp = $this->timestampHelper->getCurrentUnixTimestamp();

            $sql = "INSERT INTO {$this->table}
                (user_id, business_id, action, method, endpoint, ip_address, request_data, response_data, created_at)
                VALUES (:user_id, :business_id, :action, :method, :endpoint, :ip_address, :request_data, :response_data, :created_at)";

            $stmt = $conn->prepare($sql);

            $payload = [
                'user_id'       => $data['user_id']      ?? null,
                'business_id'   => $data['business_id']  ?? null,
                'action'        => $data['action']       ?? null,
                'method'        => $data['method']       ?? null,
                'endpoint'      => $data['endpoint']     ?? null,
                'ip_address'    => $data['ip_address']   ?? null,
                'request_data'  => json_encode($data['request_data']  ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'response_data' => json_encode($data['response_data'] ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'created_at'    => $currentTimestamp,
            ];

            $ok = $stmt->execute($payload);

            if (!$ok) {
                error_log("❌ LoggerHelper failed: " . print_r($stmt->errorInfo(), true));
            } else {
                error_log("✅ Logged: {$payload['action']} {$payload['endpoint']}");
            }

            return $ok;
        } catch (PDOException $e) {
            error_log("❌ PDO error in LoggerHelper: " . $e->getMessage());
            return false;
        } catch (\Throwable $e) {
            error_log("❌ LoggerHelper exception: " . $e->getMessage());
            return false;
        }
    }
}
