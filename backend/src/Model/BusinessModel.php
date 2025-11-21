<?php
namespace App\Model;

use PDO;
use PDOException;
use App\Helper\TimestampHelper;
use App\Helper\PasswordHelper;
use App\Helper\UniqueIdHelper;
use App\Helper\DatabaseHelper;

class BusinessModel
{
    private DatabaseHelper $pdo;
    private TimestampHelper $timestampHelper;
    private PasswordHelper $passwordHelper;
    private UniqueIdHelper $uniqueIdHelper;

    public function __construct()
    {
        $this->pdo             = new DatabaseHelper();
        $this->timestampHelper = new TimestampHelper();
        $this->passwordHelper  = new PasswordHelper();
        $this->uniqueIdHelper  = new UniqueIdHelper();
    }

    public function getBusiness(array $data)
    {
        $conn = $this->pdo->getConnection();

        try {
            // --- Extract filters ---
            $searchQuery  = $data['searchQuery'] ?? null;
            $businessId   = $data['businessId'] ?? null;
            $phone        = $data['phone'] ?? null;
            $status       = $data['status'] ?? null;
            $order        = strtoupper($data['order'] ?? 'DESC');
            $page         = max(1, (int)($data['page'] ?? 1));
            $limit        = (int)($data['limit'] ?? 25);
            $offset       = ($page - 1) * $limit;

            // --- Secure order direction ---
            $order = in_array($order, ['ASC', 'DESC'], true) ? $order : 'DESC';

            $params = [];
            $where = " WHERE 1=1 ";

            // --- Search by name or phone ---
            if (!empty($searchQuery)) {
                $where .= " AND (name LIKE :searchName OR phone LIKE :searchPhone)";
                $params[':searchName'] = trim($searchQuery) . '%';
                $params[':searchPhone'] = trim($searchQuery) . '%';
            }

            // --- Filter by business ID ---
            if (!empty($businessId)) {
                $where .= " AND business_id = :businessId";
                $params[':businessId'] = trim($businessId);
            }

            // --- Filter by phone ---
            if (!empty($phone)) {
                $where .= " AND phone LIKE :phone";
                $params[':phone'] = trim($phone) . '%';
            }

            // --- Status Filter ---
            if (!empty($status)) {
                $where .= " AND business_status = :status";
                $params[':status'] = trim($status);
            } else {
                // Default: exclude deleted
                $where .= " AND business_status != 'deleted'";
            }

            // --- Count total records ---
            $countQuery = "SELECT COUNT(*) FROM business " . $where;
            $countStmt = $conn->prepare($countQuery);
            foreach ($params as $key => $value) {
                $countStmt->bindValue($key, $value);
            }
            $countStmt->execute();
            $totalRecords = (int)$countStmt->fetchColumn();

            if ($totalRecords === 0) {
                return [
                    'status'   => false,
                    'httpCode' => 204, // No Content
                    'message'  => 'No business records found.',
                    'data'     => [],
                ];
            }

            // --- Fetch business data ---
            $selectQuery = "
                SELECT *
                FROM business
                $where
                ORDER BY id $order
            ";

            if ($limit > 0) {
                $selectQuery .= " LIMIT :offset, :limit";
            }

            $selectStmt = $conn->prepare($selectQuery);
            foreach ($params as $key => $value) {
                $selectStmt->bindValue($key, $value);
            }

            if ($limit > 0) {
                $selectStmt->bindValue(':offset', (int)$offset, \PDO::PARAM_INT);
                $selectStmt->bindValue(':limit', (int)$limit, \PDO::PARAM_INT);
            }

            $selectStmt->execute();
            $results = $selectStmt->fetchAll(\PDO::FETCH_ASSOC);

            // --- Map results cleanly ---
            $businessList = array_map(function ($row) {
                return [
                    'businessId' => $row['business_id'],
                    'name'       => $row['name'],
                    'phone'      => $row['phone'],
                    'email'      => $row['email'],
                    'addr1'      => $row['addr1'],
                    'addr2'      => $row['addr2'],
                    'logoPath'   => $row['logo_path'] ?? null,
                    'status'     => $row['business_status'],
                    'createdAt'  => $row['created_at'],
                    'updatedAt'  => $row['updated_at'],
                ];
            }, $results);

            $totalPages = $limit > 0 ? ceil($totalRecords / $limit) : 1;

            return [
                'status'   => true,
                'httpCode' => 200,
                'message'  => 'Business data fetched successfully.',
                'data'     => [
                    'records' => $businessList,
                ],
                'pagination' => [
                    'currentPage'   => $page,
                    'limit'         => $limit,
                    'totalPages'    => $totalPages,
                    'totalRecords'  => $totalRecords,
                ]
            ];

        } catch (\PDOException $e) {
            return [
                'status'   => false,
                'httpCode' => 500,
                'message'  => 'Database error: ' . $e->getMessage()
            ];
        } catch (\Exception $e) {
            return [
                'status'   => false,
                'httpCode' => 500,
                'message'  => 'Unexpected error: ' . $e->getMessage()
            ];
        } finally {
            $this->pdo->disconnect();
        }
    }

    public function createBusiness(array $data): array
    {
        $conn = $this->pdo->getConnection();
        $conn->beginTransaction(); // Start transaction
        $logoFile = $data['businessLogoFile'] ?? null;

        try {
            // Generate unique Business ID
            $uniqueBusinessId = $this->uniqueIdHelper->generate(8, '');
            $currentTimestamp = $this->timestampHelper->getCurrentUnixTimestamp();
            $currentDate = $this->timestampHelper->getFormattedTimestamp($currentTimestamp, 'Y-m-d');
            $uniqueBusinessLogoId = $this->uniqueIdHelper->generateWithTimestamp($currentTimestamp, 1, '', '');

            // === Check if business already exists by ID or phone (and not deleted)
            $checkStmt = $conn->prepare("
                SELECT *
                FROM business 
                WHERE (business_id = :business_id OR phone = :phone)
                AND business_status != 'deleted'
            ");
            $checkStmt->execute([
                ':business_id'     => $uniqueBusinessId,
                ':phone'  => $data['phone'],
            ]);

            $duplicates = $checkStmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($duplicates)) {
                foreach ($duplicates as $row) {
                    // If phone already exists on another active business
                    if ($row['phone'] === $data['phone'] && $row['business_status'] === 'inactive') {
                        $conn->rollBack();
                        return [
                            'status'   => false,
                            'message'  => 'A business with this phone number already exists as inactive',
                            'httpCode' => 409,
                        ];
                    }
                    // If same business_id already exists and it’s active
                    if ($row['phone'] === $data['phone'] && $row['business_id'] !== $uniqueBusinessId) {
                        $conn->rollBack();
                        return [
                            'status'   => false,
                            'message'  => 'A business with this phone number already exists',
                            'httpCode' => 409,
                        ];
                    }
                    // If same business_id already exists and it’s active
                    if ($row['business_id'] === $uniqueBusinessId) {
                        $conn->rollBack();
                        return [
                            'status'   => false,
                            'message'  => 'Business ID already exists',
                            'httpCode' => 409,
                        ];
                    }
                }
            }

            // Current timestamp
            $createdAt = $currentTimestamp;

            // Save uploaded file
            $uploadDir = __DIR__ . '/../../uploads/business/logo/';
            if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

            $ext = pathinfo($logoFile->getClientFilename(), PATHINFO_EXTENSION);
            $newFileName = $uniqueBusinessLogoId . '.' . strtolower($ext);
            $savePath = $uploadDir . $newFileName;

            $logoFile->moveTo($savePath);
            $logoPath = strtolower('/uploads/business/logo/' . $newFileName);

            // Insert Business
            $stmt = $conn->prepare("
                INSERT INTO business (
                    business_id, name, phone, email, addr1, addr2, logo_path, business_status, created_at, updated_at
                ) VALUES (
                    :business_id, :name, :phone, :email, :addr1, :addr2, :logo_path, :business_status, :created_at, :updated_at
                )
            ");

            $inserted = $stmt->execute([
                ':business_id'     => $uniqueBusinessId,
                ':name'           => $data['name'],
                ':phone'          => $data['phone'],
                ':email'          => $data['email'] ?? null,
                ':addr1'          => $data['addr1'] ?? null,
                ':addr2'          => $data['addr2'] ?? null,
                ':logo_path'      => $logoPath,
                ':business_status' => 'active',
                ':created_at'     => $createdAt,
                ':updated_at'     => $createdAt,
            ]);

            if (!$inserted) {
                $conn->rollBack();
                return [
                    'status'   => false,
                    'message'  => 'Failed to create business',
                    'httpCode' => 500,
                ];
            }
            // Get last inserted ID
            $lastInsertId = (int) $conn->lastInsertId();
            $conn->commit();

            return [
                'status'   => true,
                'message'  => 'Business created successfully',
                'httpCode' => 201,
                'data'     => [
                    'businessId' => $uniqueBusinessId,
                    'phone'      => $data['phone'],
                    'name'       => $data['name']
                ],
            ];

        } catch (PDOException $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            return [
                'status'   => false,
                'message'  => 'Database Error: ' . $e->getMessage(),
                'httpCode' => 500,
            ];
        } finally {
            $this->pdo->disconnect();
        }
    }
        
    public function updateBusiness(string $businessId, array $data): array
    {
        $conn = $this->pdo->getConnection();
        $conn->beginTransaction();

        $currentTimestamp = $this->timestampHelper->getCurrentUnixTimestamp();
        $uniqueBusinessLogoId = $this->uniqueIdHelper->generateWithTimestamp($currentTimestamp, 1, '', '');

        try {
            $checkStmt = $conn->prepare("SELECT * FROM business WHERE business_id = :id LIMIT 1");
            $checkStmt->execute([':id' => $businessId]);
            $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if (!$existing) {
                return [
                    'status' => false,
                    'message' => 'Business not found',
                    'httpCode' => 409,
                ];
            }

            $fields = [];
            $params = [':id' => $businessId];

            $map = [
                'name'   => 'name',
                'phone'  => 'phone',
                'email'  => 'email',
                'addr1'  => 'addr1',
                'addr2'  => 'addr2',
                'status' => 'business_status',
            ];

            foreach ($map as $key => $col) {
                if (isset($data[$key]) && $data[$key] !== $existing[$col]) {
                    $fields[] = "$col = :$key";
                    $params[":$key"] = $data[$key];
                }
            }

            $logoFilePath = null;
            if (!empty($data['businessLogo'])) {
                $logoFile = $data['businessLogo'];

                // === Case 1: UploadedFile object ===
                if ($logoFile instanceof \Slim\Psr7\UploadedFile) {
                    $uploadDir = __DIR__ . '/../../uploads/business/logo/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                    $ext = strtolower(pathinfo($logoFile->getClientFilename(), PATHINFO_EXTENSION));
                    $safeName = strtolower($uniqueBusinessLogoId . '.' . $ext);
                    $fullPath = $uploadDir . $safeName;

                    // Binary-safe move
                    $logoFile->moveTo($fullPath);
                    $logoFilePath = '/uploads/business/logo/' . $safeName;
                }

                // === Case 2: Manual PUT parsed file (array with tmp_name) ===
                elseif (is_array($logoFile) && isset($logoFile['tmp_name']) && file_exists($logoFile['tmp_name'])) {
                    $uploadDir = __DIR__ . '/../../uploads/business/logo/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                    $ext = strtolower(pathinfo($logoFile['name'], PATHINFO_EXTENSION));
                    $safeName = strtolower($uniqueBusinessLogoId . '.' . $ext);
                    $fullPath = $uploadDir . $safeName;

                    // Use binary-safe copy instead of rename
                    $src = fopen($logoFile['tmp_name'], 'rb');
                    $dst = fopen($fullPath, 'wb');
                    stream_copy_to_stream($src, $dst);
                    fclose($src);
                    fclose($dst);

                    // Delete temp file safely
                    @unlink($logoFile['tmp_name']);

                    $logoFilePath = '/uploads/business/logo/' . $safeName;
                }

                // === Case 3: Base64 or existing path ===
                elseif (is_string($logoFile)) {
                    // Check if it's a base64 string
                    if (preg_match('/^data:image\/(\w+);base64,/', $logoFile, $matches)) {
                        $ext = strtolower($matches[1]);
                        $uploadDir = __DIR__ . '/../../uploads/business/logo/';
                        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                        $safeName = strtolower($uniqueBusinessLogoId . '.' . $ext);
                        $fullPath = $uploadDir . $safeName;

                        $base64Data = substr($logoFile, strpos($logoFile, ',') + 1);
                        $decoded = base64_decode($base64Data, true);
                        if ($decoded === false) {
                            throw new \RuntimeException("Invalid base64 image data");
                        }

                        // Binary-safe write
                        file_put_contents($fullPath, $decoded);
                        $logoFilePath = '/uploads/business/logo/' . $safeName;
                    } else {
                        // treat as existing file path
                        $logoFilePath = $logoFile;
                    }
                }

                if (!empty($logoFilePath)) {
                    $fields[] = "logo_path = :logo_path";
                    $params[':logo_path'] = $logoFilePath;
                }
            }

            if (empty($fields)) {
                $conn->rollBack();
                return [
                    'status' => false,
                    'message' => 'No valid changes detected',
                    'httpCode' => 200,
                ];
            }

            $fields[] = "updated_at = :updated_at";
            $params[':updated_at'] = $currentTimestamp;

            $sql = "UPDATE business SET " . implode(', ', $fields) . " WHERE business_id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);

            $conn->commit();

            // Remove old logo if exists
            if (!empty($existing['logo_path'])) {
                $oldPath = __DIR__ . '/../../' . $existing['logo_path'];
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }

            return [
                'status' => true,
                'message' => 'Business updated successfully',
                'httpCode' => 200,
                'data' => [
                    'businessId' => $businessId,
                    'name' => $data['name'] ?? $existing['name'],
                    'phone' => $data['phone'] ?? $existing['phone'],
                    'email' => $data['email'] ?? $existing['email'],
                    'addr1' => $data['addr1'] ?? $existing['addr1'],
                    'addr2' => $data['addr2'] ?? $existing['addr2'],
                    'logoPath' => $logoFilePath ?? $existing['logo_path'],
                    'status' => $data['status'] ?? $existing['business_status'],
                    'createdAt' => $existing['created_at'],
                    'updatedAt' => $currentTimestamp,
                ],
            ];

        } catch (\Throwable $e) {
            if ($conn->inTransaction()) $conn->rollBack();
            return [
                'status' => false,
                'message' => 'Database Error: ' . $e->getMessage(),
                'httpCode' => 500,
            ];
        } finally {
            $this->pdo->disconnect();
        }
    }

    public function deleteBusiness(string $businessId): array
    {
        $conn = $this->pdo->getConnection();
        $conn->beginTransaction();

        try {
            // --- Check if Business exists ---
            $checkStmt = $conn->prepare("SELECT * FROM business WHERE business_id = :business_id LIMIT 1");
            $checkStmt->execute([':business_id' => $businessId]);
            $existingBusiness = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if (!$existingBusiness) {
                $conn->rollBack();
                return [
                    'status'   => false,
                    'message'  => 'Business not found',
                    'httpCode' => 409,
                ];
            }

            if ($existingBusiness['business_status'] === 'deleted') {
                $conn->rollBack();
                return [
                    'status'   => false,
                    'message'  => 'Business already deleted',
                    'httpCode' => 400,
                ];
            }

            if ($existingBusiness['business_status'] === 'inactive') {
                $conn->rollBack();
                return [
                    'status'   => false,
                    'message'  => 'Inactive business can not be deleted',
                    'httpCode' => 400,
                ];
            }

            // --- Soft delete by updating status ---
            $updateStmt = $conn->prepare("
                UPDATE business
                SET business_status = :status
                WHERE business_id = :business_id
            ");
            $deleted = $updateStmt->execute([
                ':status'     => 'deleted',
                ':business_id' => $businessId,
            ]);

            if (!$deleted) {
                $conn->rollBack();
                return [
                    'status'   => false,
                    'message'  => 'Failed to delete Business',
                    'httpCode' => 500,
                ];
            }

            $conn->commit();

            return [
                'status'   => true,
                'message'  => 'Business deleted successfully',
                'httpCode' => 200,
                'data'     => [
                    'businessId'    => $businessId,
                    'name'         => $existingBusiness['name'],
                ],
            ];

        } catch (PDOException $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            return [
                'status'   => false,
                'message'  => 'Database Error: ' . $e->getMessage(),
                'httpCode' => 500,
            ];
        } finally {
            $this->pdo->disconnect();
        }
    }

    public function fetchDashboardStats(array $data = []): array
    {
        $conn = $this->pdo->getConnection();

        try {
            $businessId = $data['businessId'] ?? null;
            $status     = $data['status'] ?? 'active';
            $page       = (int)($data['page'] ?? 1);
            $limit      = (int)($data['limit'] ?? 6);
            $offset     = ($page - 1) * $limit;

            $whereInvoices = " WHERE invoice_status = :status ";
            $whereBusiness = " WHERE business_status = :status ";
            $whereLogs     = " WHERE 1=1 ";

            $paramsInvoices = [':status' => trim($status)];
            $paramsBusiness = [':status' => trim($status)];
            $paramsLogs     = [];

            if (!empty($businessId)) {
                $whereInvoices .= " AND business_id = :businessId ";
                $whereBusiness .= " AND business_id = :businessId ";
                $whereLogs     .= " AND business_id = :businessId ";

                $paramsInvoices[':businessId'] = $businessId;
                $paramsBusiness[':businessId'] = $businessId;
                $paramsLogs[':businessId']     = $businessId;
            }

            // ======================
            // DASHBOARD STATS
            // ======================
            $query = "
                SELECT 
                    COUNT(*) AS total_invoices,
                    SUM(CASE WHEN DATE(FROM_UNIXTIME(created_at)) = CURDATE() THEN 1 ELSE 0 END) AS today_invoices,
                    SUM(CASE WHEN DATE(FROM_UNIXTIME(created_at)) = DATE_SUB(CURDATE(), INTERVAL 1 DAY) THEN 1 ELSE 0 END) AS yesterday_invoices,
                    IFNULL(SUM(amount), 0) AS total_sales,
                    IFNULL(SUM(CASE WHEN DATE(FROM_UNIXTIME(created_at)) = CURDATE() THEN amount ELSE 0 END), 0) AS today_sales,
                    IFNULL(SUM(CASE WHEN DATE(FROM_UNIXTIME(created_at)) = DATE_SUB(CURDATE(), INTERVAL 1 DAY) THEN amount ELSE 0 END), 0) AS yesterday_sales
                FROM invoices
                $whereInvoices
            ";
            $stmt = $conn->prepare($query);
            foreach ($paramsInvoices as $k => $v) $stmt->bindValue($k, $v);
            $stmt->execute();
            $invoiceStats = $stmt->fetch(\PDO::FETCH_ASSOC);

            // ======================
            // TOTAL BUSINESS
            // ======================
            $stmt = $conn->prepare("SELECT COUNT(*) FROM business $whereBusiness");
            foreach ($paramsBusiness as $k => $v) $stmt->bindValue($k, $v);
            $stmt->execute();
            $totalBusiness = (int)$stmt->fetchColumn();

            // ======================
            // TOTAL LOGS
            // ======================
            $stmt = $conn->prepare("SELECT COUNT(*) FROM activity_logs $whereLogs");
            foreach ($paramsLogs as $k => $v) $stmt->bindValue($k, $v);
            $stmt->execute();
            $totalLogs = (int)$stmt->fetchColumn();

            // ======================
            // RECENT INVOICES (Paginated)
            // ======================
            // Count total invoices (for pagination)
            $countQuery = "SELECT COUNT(*) FROM invoices $whereInvoices";
            $stmt = $conn->prepare($countQuery);
            foreach ($paramsInvoices as $k => $v) $stmt->bindValue($k, $v);
            $stmt->execute();
            $totalInvoicesCount = (int)$stmt->fetchColumn();
            $totalPages = max(1, ceil($totalInvoicesCount / $limit));

            // Fetch limited recent invoices
            $stmt = $conn->prepare("
                SELECT 
                    invoice_id, 
                    invoice_number, 
                    name, 
                    amount, 
                    DATE(FROM_UNIXTIME(created_at)) AS invoice_date 
                FROM invoices 
                $whereInvoices
                ORDER BY id DESC 
                LIMIT :limit OFFSET :offset
            ");
            foreach ($paramsInvoices as $k => $v) $stmt->bindValue($k, $v);
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
            $stmt->execute();
            $recentInvoices = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // ======================
            // CHART DATA (LAST 7 DAYS)
            // ======================
            $chartQuery = "
                SELECT 
                    DATE(FROM_UNIXTIME(created_at)) AS date_label,
                    COUNT(*) AS invoice_count,
                    IFNULL(SUM(amount), 0) AS total_sales
                FROM invoices
                $whereInvoices
                    AND DATE(FROM_UNIXTIME(created_at)) >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                GROUP BY DATE(FROM_UNIXTIME(created_at))
                ORDER BY DATE(FROM_UNIXTIME(created_at)) ASC
            ";
            $stmt = $conn->prepare($chartQuery);
            foreach ($paramsInvoices as $k => $v) $stmt->bindValue($k, $v);
            $stmt->execute();
            $chartResults = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $labels = [];
            $invoiceCounts = [];
            $salesValues = [];

            for ($i = 6; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $day  = date('D', strtotime($date));
                $labels[] = $day;
                $record = array_values(array_filter($chartResults, fn($r) => $r['date_label'] === $date))[0] ?? null;
                $invoiceCounts[] = $record ? (int)$record['invoice_count'] : 0;
                $salesValues[] = $record ? (float)$record['total_sales'] : 0.0;
            }

            // ======================
            // Final Data
            // ======================
            $records = [[
                'totalInvoices'     => (int)($invoiceStats['total_invoices'] ?? 0),
                'todayInvoices'     => (int)($invoiceStats['today_invoices'] ?? 0),
                'yesterdayInvoices' => (int)($invoiceStats['yesterday_invoices'] ?? 0),
                'totalBusiness'     => $totalBusiness,
                'totalSales'        => number_format((float)($invoiceStats['total_sales'] ?? 0), 2),
                'todaySales'        => number_format((float)($invoiceStats['today_sales'] ?? 0), 2),
                'yesterdaySales'    => number_format((float)($invoiceStats['yesterday_sales'] ?? 0), 2),
                'totalLogs'         => $totalLogs,
                'recentInvoices'    => $recentInvoicesDesc = array_slice($recentInvoices, 0, 6),
                'last7Days'         => [
                    'labels'   => $labels,
                    'invoices' => $invoiceCounts,
                    'sales'    => $salesValues,
                ]
            ]];

            // ======================
            // Return Standardized Response
            // ======================
            return [
                'status'   => true,
                'httpCode' => 200,
                'message'  => 'Dashboard stats fetched successfully.',
                'data'     => ['records' => $records],
                'pagination' => [
                    'currentPage'  => $page,
                    'limit'        => $limit,
                    'totalPages'   => $totalPages,
                    'totalRecords' => $totalInvoicesCount
                ]
            ];

        } catch (\Throwable $e) {
            return [
                'status'   => false,
                'httpCode' => 500,
                'message'  => 'Error: ' . $e->getMessage()
            ];
        } finally {
            $this->pdo->disconnect();
        }
    }

    public function fetchActivityLogs(array $data = []): array
    {
        $conn = $this->pdo->getConnection();

        try {
            $searchQuery = trim($data['searchQuery'] ?? '');
            $businessId  = $data['businessId'] ?? null;
            $order       = strtoupper($data['order'] ?? 'DESC');
            $page        = (int)($data['page'] ?? 1);
            $limit       = (int)($data['limit'] ?? 25);
            $offset      = ($page - 1) * $limit;

            // Base filters
            $where = " WHERE 1=1 ";
            $params = [];

            if (!empty($businessId)) {
                $where .= " AND business_id = :businessId ";
                $params[':businessId'] = $businessId;
            }

            if (!empty($searchQuery)) {
                $where .= " AND (action LIKE :searchQuery OR endpoint LIKE :searchQuery OR ip_address LIKE :searchQuery OR user_id LIKE :searchQuery)";
                $params[':searchQuery'] = "%$searchQuery%";
            }

            // Count total records
            $stmt = $conn->prepare("SELECT COUNT(*) FROM activity_logs $where");
            foreach ($params as $k => $v) $stmt->bindValue($k, $v);
            $stmt->execute();
            $totalRecords = (int)$stmt->fetchColumn();

            // Pagination math
            $totalPages = max(1, ceil($totalRecords / $limit));

            // Fetch paginated results
            $stmt = $conn->prepare("
                SELECT 
                    user_id,
                    business_id,
                    action,
                    method,
                    endpoint,
                    ip_address,
                    request_data,
                    response_data,
                    created_at,
                    FROM_UNIXTIME(created_at) AS created_datetime
                FROM activity_logs
                $where
                ORDER BY created_at $order
                LIMIT :limit OFFSET :offset
            ");
            foreach ($params as $k => $v) $stmt->bindValue($k, $v);
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
            $stmt->execute();

            $logs = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $records = array_map(function ($log) {
                return [
                    'userId'        => $log['user_id'],
                    'businessId'    => $log['business_id'],
                    'action'        => $log['action'],
                    // 'method'        => strtoupper($log['method']),
                    // 'endpoint'      => $log['endpoint'],
                    'ipAddress'     => $log['ip_address'],
                    // 'requestData'   => json_decode($log['request_data'] ?? '[]', true),
                    // 'responseData'  => json_decode($log['response_data'] ?? '[]', true),
                    'createdAtText' => date('Y-m-d H:i:s', (int)$log['created_at']),
                ];
            }, $logs);

            return [
                'status'   => true,
                'httpCode' => 200,
                'message'  => 'Activity logs fetched successfully.',
                'data'     => [ 'records' => $records ],
                'pagination' => [
                    'currentPage'  => $page,
                    'limit'        => $limit,
                    'totalPages'   => $totalPages,
                    'totalRecords' => $totalRecords,
                ]
            ];

        } catch (\Throwable $e) {
            return [
                'status'   => false,
                'httpCode' => 500,
                'message'  => 'Error: ' . $e->getMessage()
            ];
        } finally {
            $this->pdo->disconnect();
        }
    }

    // --- Helper: return error consistently ---
    private function fail($conn, $message, $httpCode = 400)
    {
        $conn->rollBack();
        return [
            'status'   => false,
            'message'  => $message,
            'httpCode' => $httpCode,
        ];
    }


}
