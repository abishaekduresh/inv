<?php
namespace App\Model;

use PDO;
use PDOException;
use App\Helper\TimestampHelper;
use App\Helper\PasswordHelper;
use App\Helper\UniqueIdHelper;
use App\Helper\DatabaseHelper;

class InvoiceModel
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

    public function getInvoice(array $data)
    {
        $conn = $this->pdo->getConnection();

        try {
            // === Input parameters ===
            $searchQuery   = $data['searchQuery'] ?? null;
            $invoiceId     = $data['invoiceId'] ?? null;
            $phone         = $data['phone'] ?? null;
            $invoiceNumber = $data['invoiceNumber'] ?? null;
            $invoiceType   = $data['invoiceType'] ?? null;
            $place         = $data['place'] ?? null;
            $dob           = $data['dob'] ?? null;
            $fromDate      = $data['fromDate'] ?? null;
            $toDate        = $data['toDate'] ?? null;
            $status        = $data['status'] ?? 'active';
            $page          = max(1, (int)($data['page'] ?? 1));
            $limit         = isset($data['limit']) && (int)$data['limit'] > 0 ? (int)$data['limit'] : null;
            $offset        = $limit ? ($page - 1) * $limit : null;

            // === Safe ORDER BY ===
            $allowedOrderBy = [
                'id', 'invoice_id', 'invoice_number', 'invoice_date',
                'name', 'place', 'amount', 'created_at'
            ];
            $orderBy = in_array($data['orderBy'] ?? 'id', $allowedOrderBy, true)
                ? $data['orderBy']
                : 'id';

            $order = strtoupper(trim($data['order'] ?? 'DESC'));
            $order = ($order === 'ASC') ? 'ASC' : 'DESC';

            // === Base filter ===
            $params = [];
            $where = " WHERE 1=1 ";

            // === Dynamic filters ===
            if (!empty($searchQuery)) {
                $where .= " AND (name LIKE :name OR place LIKE :place OR phone LIKE :phone)";
                $params[':name']  = trim($searchQuery) . '%';
                $params[':place'] = trim($searchQuery) . '%';
                $params[':phone'] = trim($searchQuery) . '%';
            }

            if (!empty($invoiceId)) {
                $where .= " AND invoice_id LIKE :invoiceId";
                $params[':invoiceId'] = trim($invoiceId) . '%';
            }

            if (!empty($phone)) {
                $where .= " AND phone = :phoneExact";
                $params[':phoneExact'] = trim($phone);
            }

            if (!empty($invoiceNumber)) {
                $where .= " AND invoice_number = :invoiceNumber";
                $params[':invoiceNumber'] = trim($invoiceNumber);
            }

            if (!empty($invoiceType)) {
                $where .= " AND invoice_type = :invoiceType";
                $params[':invoiceType'] = trim($invoiceType);
            }

            if (!empty($place)) {
                $where .= " AND place LIKE :placeExact";
                $params[':placeExact'] = trim($place) . '%';
            }

            if (!empty($dob)) {
                $where .= " AND dob = :dob";
                $params[':dob'] = $dob;
            }

            if (!empty($fromDate) && !empty($toDate)) {
                $where .= " AND invoice_date BETWEEN :fromDate AND :toDate";
                $params[':fromDate'] = $fromDate;
                $params[':toDate']   = $toDate;
            }

            // === Status Filter ===
            if (!empty($status)) {
                $where .= " AND invoice_status = :status";
                $params[':status'] = trim($status);
            } else {
                $where .= " AND invoice_status != :status";
                $params[':status'] = 'deleted';
            }

            // === Count total ===
            $countQuery = "SELECT COUNT(*) FROM invoices " . $where;
            $countStmt = $conn->prepare($countQuery);
            foreach ($params as $key => $value) {
                $countStmt->bindValue($key, $value);
            }
            $countStmt->execute();
            $totalRecords = (int) $countStmt->fetchColumn();

            if ($totalRecords === 0) {
                return [
                    'status'   => false,
                    'httpCode' => 409,
                    'message'  => 'No invoices found matching the criteria.',
                    'data'     => null
                ];
            }

            // === Build main query ===
            $selectQuery = "SELECT * FROM invoices " . $where . " ORDER BY " . $orderBy . " " . $order;
            if ($limit) {
                $selectQuery .= " LIMIT :offset, :limit";
            }

            $selectStmt = $conn->prepare($selectQuery);
            foreach ($params as $key => $value) {
                $selectStmt->bindValue($key, $value);
            }

            if ($limit) {
                $selectStmt->bindValue(':offset', (int)$offset, \PDO::PARAM_INT);
                $selectStmt->bindValue(':limit', (int)$limit, \PDO::PARAM_INT);
            }

            $selectStmt->execute();
            $results = $selectStmt->fetchAll(\PDO::FETCH_ASSOC);

            // === Transform and format ===
            $invoices = array_map(function ($row) {
                $age = $this->timestampHelper->calculateAge($row['dob'] ?? null, $row['invoice_date'] ?? null);
                return [
                    'invoiceId'     => $row['invoice_id'],
                    'invoiceType'   => isset($row['invoice_type']) ? ucwords(strtolower($row['invoice_type'])) : null,
                    'invoiceNumber' => $row['invoice_number'],
                    'invoiceDate'   => $row['invoice_date'],
                    'name'          => $row['name'],
                    'phone'         => $row['phone'],
                    'dob'           => $row['dob'],
                    'age'           => $age,
                    'place'         => $row['place'],
                    'frame'         => $row['frame'],
                    'lence'         => $row['lence'],
                    'power'         => [
                        'rSph'  => $row['r_sph'],
                        'rCyl'  => $row['r_cyl'],
                        'rAxis' => $row['r_axis'],
                        'rVia'  => $row['r_via'],
                        'rAdd'  => $row['r_add'],
                        'rPd'   => $row['r_pd'],
                        'lSph'  => $row['l_sph'],
                        'lCyl'  => $row['l_cyl'],
                        'lAxis' => $row['l_axis'],
                        'lVia'  => $row['l_via'],
                        'lAdd'  => $row['l_add'],
                        'lPd'   => $row['l_pd'],
                    ],
                    'amount'        => $row['amount'],
                    'offer'         => $row['offer'] ?? '-',
                    'claim'         => $row['claim'],
                    'remark'        => $row['remark'],
                    'paymentMode'   => $row['payment_mode'],
                    'invoiceStatus' => $row['invoice_status'],
                    'createdAt'     => $row['created_at'],
                ];
            }, $results);

            $totalPages = $limit ? ceil($totalRecords / $limit) : 1;

            return [
                'status'   => true,
                'httpCode' => 200,
                'message'  => 'Invoices fetched successfully',
                'data'     => ['invoices' => $invoices],
                'pagination' => [
                    'currentPage'  => $page,
                    'limit'        => $limit,
                    'totalPages'   => $totalPages,
                    'totalRecords' => $totalRecords,
                ],
            ];
        } catch (\PDOException $e) {
            return [
                'status'   => false,
                'httpCode' => 500,
                'message'  => 'Database error: ' . $e->getMessage(),
            ];
        } catch (\Exception $e) {
            return [
                'status'   => false,
                'httpCode' => 500,
                'message'  => 'Unexpected error: ' . $e->getMessage(),
            ];
        } finally {
            $this->pdo->disconnect();
        }
    }

    public function createInvoice(array $data): array
    {
        $conn = $this->pdo->getConnection();
        $conn->beginTransaction(); // Start transaction

        try {
            // Generate unique invoice ID
            $uniqueInvoiceId = $this->uniqueIdHelper->generate(8, '');
            $currentTimestamp = $this->timestampHelper->getCurrentUnixTimestamp();
            $currentDate = $this->timestampHelper->getFormattedTimestamp($currentTimestamp, 'Y-m-d');

            // Check if invoice_id already exists with active invoice (optional)
            $checkStmt = $conn->prepare("
                SELECT id 
                FROM invoices 
                WHERE invoice_id = :invoice_id AND invoice_status = 'active'
            ");
            $checkStmt->execute([
                ':invoice_id' => $uniqueInvoiceId
            ]);

            if ($checkStmt->rowCount() > 0) {
                $conn->rollBack();
                return [
                    'status'   => false,
                    'message'  => 'An active invoice for this phone already exists',
                    'httpCode' => 409,
                ];
            }

            // Current timestamp
            $createdAt = $currentTimestamp;

            // Insert invoice
            $stmt = $conn->prepare("
                INSERT INTO invoices (
                    invoice_id, invoice_type, invoice_date, invoice_number, name, phone, dob, place, frame, lence,
                    r_sph, r_cyl, r_axis, r_via, r_add, r_pd,
                    l_sph, l_cyl, l_axis, l_via, l_add, l_pd,
                    amount, offer, claim, remark, payment_mode, invoice_status, created_at
                ) VALUES (
                    :invoice_id, :invoice_type, :invoice_date, :invoice_number, :name, :phone, :dob, :place, :frame, :lence,
                    :r_sph, :r_cyl, :r_axis, :r_via, :r_add, :r_pd,
                    :l_sph, :l_cyl, :l_axis, :l_via, :l_add, :l_pd,
                    :amount, :offer, :claim, :remark, :payment_mode, :invoice_status, :created_at
                )
            ");

            $inserted = $stmt->execute([
                ':invoice_id'     => $uniqueInvoiceId,
                ':invoice_type'   => $data['invoiceType'] ?? null,
                ':invoice_date'   => $data['invoiceDate'] ?? $currentDate,
                ':invoice_number' => $data['invoiceNumber'],
                ':name'           => strtoupper($data['name']),
                ':phone'          => $data['phone'],
                ':dob'            => $data['dob'] ?? null,
                ':place'          => $data['place'] ?? null,
                ':frame'          => $data['frame'] ?? null,
                ':lence'          => $data['lence'] ?? null,
                ':r_sph'         => $data['rSph'] ?? null,
                ':r_cyl'          => $data['rCyl'] ?? null,
                ':r_axis'         => $data['rAxis'] ?? null,
                ':r_via'          => $data['rVia'] ?? null,
                ':r_add'          => $data['rAdd'] ?? null,
                ':r_pd'           => $data['rPd'] ?? null,
                ':l_sph'         => $data['lSph'] ?? null,
                ':l_cyl'          => $data['lCyl'] ?? null,
                ':l_axis'         => $data['lAxis'] ?? null,
                ':l_via'          => $data['lVia'] ?? null,
                ':l_add'          => $data['lAdd'] ?? null,
                ':l_pd'           => $data['lPd'] ?? null,
                ':amount'         => $data['amount'] ?? 0.00,
                ':offer'          => $data['offer'] ?? null,
                ':claim'          => $data['claim'] ?? null,
                ':remark'         => $data['remark'] ?? null,
                ':payment_mode'   => $data['paymentMode'] ?? null,
                ':invoice_status' => 'active',
                ':created_at'     => $createdAt,
            ]);

            if (!$inserted) {
                $conn->rollBack();
                return [
                    'status'   => false,
                    'message'  => 'Failed to create invoice',
                    'httpCode' => 500,
                ];
            }
            // Get last inserted ID
            $lastInsertId = (int) $conn->lastInsertId();
            $conn->commit();

            return [
                'status'   => true,
                'message'  => 'Invoice created successfully',
                'httpCode' => 201,
                'data'     => [
                    'id'    => $lastInsertId,
                    'invoiceId' => $uniqueInvoiceId,
                    'invoiceNumber' => $data['invoiceNumber'],
                    'name' => strtoupper($data['name'])
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

    public function updateInvoice(string $invoiceId, array $data): array
    {
        $conn = $this->pdo->getConnection();
        $conn->beginTransaction(); // Start transaction

        try {
            // --- Check if invoice exists ---
            $checkStmt = $conn->prepare("
                SELECT * 
                FROM invoices 
                WHERE invoice_id = :invoice_id 
                LIMIT 1
            ");
            $checkStmt->execute([':invoice_id' => $invoiceId]);
            $existingInvoice = $checkStmt->fetch(PDO::FETCH_ASSOC);

            // --- Check existence ---
            if (!$existingInvoice) {
                return $this->fail($conn, 'Invoice not found', 409);
            }

            // --- Deleted invoice check ---
            if (($existingInvoice['invoice_status'] ?? null) === 'deleted') {
                return $this->fail($conn, 'Deleted invoice cannot be updated', 400);
            }

            // --- Determine requested status (or keep same) ---
            $existingStatus  = $existingInvoice['invoice_status'] ?? null;
            $requestedStatus = $data['invoiceStatus'] ?? $existingStatus;

            // --- Inactive invoice rule ---
            if ($existingStatus === 'inactive' && $requestedStatus !== 'active') {
                return $this->fail($conn, 'Inactive invoice cannot be updated', 400);
            }

            // --- Prevent duplicate active invoice number or invalid change ---
            $existingNumber     = $existingInvoice['invoice_number'] ?? null;
            $existingInvoiceId  = $existingInvoice['invoice_id'] ?? null;
            $existingStatus     = $existingInvoice['invoice_status'] ?? null;
            $updateNumber          = $data['invoiceNumber'] ?? null;

            // --- Check if invoice number was provided in the update payload ---
            if (!empty($updateNumber) && $updateNumber !== $existingNumber) {
                $stmt = $conn->prepare("
                    SELECT COUNT(*) AS count
                    FROM invoices
                    WHERE invoice_status = 'active'
                    AND invoice_number = :invoice_number
                    AND invoice_id != :invoice_id
                ");
                $stmt->execute([
                    ':invoice_number' => $updateNumber,
                    ':invoice_id'     => $invoiceId,
                ]);
                $count = (int)$stmt->fetchColumn();

                if ($count >= 2) {
                    return $this->fail($conn, 'Invoice number exists more than twice', 400);
                } elseif ($count >= 1) {
                    return $this->fail($conn, 'Invoice number already exists', 400);
                }
            }

            // --- Prepare dynamic update fields ---
            $fields = [];
            $params = [':invoice_id' => $invoiceId];

            $mapping = [
                'invoiceType'   => 'invoice_type',
                'invoiceNumber' => 'invoice_number',
                'invoiceDate'   => 'invoice_date',
                'name'          => 'name',
                'phone'         => 'phone',
                'dob'           => 'dob',
                'place'         => 'place',
                'frame'         => 'frame',
                'lence'         => 'lence',
                'rSph'          => 'r_sph',
                'rCyl'          => 'r_cyl',
                'rAxis'         => 'r_axis',
                'rVia'          => 'r_via',
                'rAdd'          => 'r_add',
                'rPd'           => 'r_pd',
                'lSph'          => 'l_sph',
                'lCyl'          => 'l_cyl',
                'lAxis'         => 'l_axis',
                'lVia'          => 'l_via',
                'lAdd'          => 'l_add',
                'lPd'           => 'l_pd',
                'amount'        => 'amount',
                'offer'         => 'offer',
                'claim'         => 'claim',
                'remark'        => 'remark',
                'paymentMode'   => 'payment_mode',
                'invoiceStatus' => 'invoice_status'
            ];

            foreach ($mapping as $key => $column) {
                if (isset($data[$key])) {
                    $fields[] = "$column = :$key";
                    if (in_array($key, ['name'])) {
                        $params[":$key"] = strtoupper($data[$key]);
                    } elseif (in_array($key, ['amount'])) {
                        $params[":$key"] = (float)$data[$key];
                    } elseif (in_array($key, ['phone', 'invoiceNumber'])) {
                        $params[":$key"] = (int)$data[$key];
                    } elseif (in_array($key, ['invoiceDate', 'dob']) && !empty($data[$key])) {
                        $params[":$key"] = date('Y-m-d', strtotime($data[$key]));
                    } else {
                        $params[":$key"] = $data[$key];
                    }
                }
            }

            if (empty($fields)) {
                $conn->rollBack();
                return [
                    'status'   => false,
                    'message'  => 'No fields to update',
                    'httpCode' => 400,
                ];
            }

            // --- Build and execute update query ---
            $updateStmt = $conn->prepare("
                UPDATE invoices
                SET " . implode(', ', $fields) . "
                WHERE invoice_id = :invoice_id
            ");

            $updated = $updateStmt->execute($params);

            if (!$updated) {
                $conn->rollBack();
                return [
                    'status'   => false,
                    'message'  => 'Failed to update invoice',
                    'httpCode' => 500,
                ];
            }

            $conn->commit();

            return [
                'status'   => true,
                'message'  => 'Invoice updated successfully',
                'httpCode' => 200,
                'data'     => [
                    'invoiceId' => $invoiceId,
                    'name' => $data['name'] ?? null,
                    // 'updatedFields' => array_keys($data),
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

    public function deleteInvoice(string $invoiceId): array
    {
        $conn = $this->pdo->getConnection();
        $conn->beginTransaction();

        try {
            // --- Check if invoice exists ---
            $checkStmt = $conn->prepare("SELECT * FROM invoices WHERE invoice_id = :invoice_id LIMIT 1");
            $checkStmt->execute([':invoice_id' => $invoiceId]);
            $existingInvoice = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if (!$existingInvoice) {
                $conn->rollBack();
                return [
                    'status'   => false,
                    'message'  => 'Invoice not found',
                    'httpCode' => 409,
                ];
            }

            if ($existingInvoice['invoice_status'] === 'deleted') {
                $conn->rollBack();
                return [
                    'status'   => false,
                    'message'  => 'Invoice already deleted',
                    'httpCode' => 400,
                ];
            }

            if ($existingInvoice['invoice_status'] === 'inactive') {
                $conn->rollBack();
                return [
                    'status'   => false,
                    'message'  => 'Inactive invoice can not be deleted',
                    'httpCode' => 400,
                ];
            }

            // --- Soft delete by updating status ---
            $updateStmt = $conn->prepare("
                UPDATE invoices
                SET invoice_status = :status
                WHERE invoice_id = :invoice_id
            ");
            $deleted = $updateStmt->execute([
                ':status'     => 'deleted',
                ':invoice_id' => $invoiceId,
            ]);

            if (!$deleted) {
                $conn->rollBack();
                return [
                    'status'   => false,
                    'message'  => 'Failed to delete invoice',
                    'httpCode' => 500,
                ];
            }

            $conn->commit();

            return [
                'status'   => true,
                'message'  => 'Invoice deleted successfully',
                'httpCode' => 200,
                'data'     => [
                    'invoiceId'    => $invoiceId,
                    'invoiceNumber'=> $existingInvoice['invoice_number'],
                    'name'         => $existingInvoice['name'],
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

    public function getSharedInvoice(array $data)
    {
        $conn = $this->pdo->getConnection();

        try {
            $invoiceId     = $data['invoiceId'] ?? null;

            $params = [];
            $where = " WHERE 1=1 ";

            if (!empty($invoiceId)) {
                $where .= " AND invoice_id = :invoiceId";
                $params[':invoiceId'] = trim($invoiceId);
            }

            if(!empty($status)) {
                $where .= " AND invoice_status = :status";
                $params[':status'] = trim($status);
            }

            // Fetch invoice data
            $selectQuery = "SELECT * FROM invoices " . $where . "";

            $selectStmt = $conn->prepare($selectQuery);
            foreach ($params as $key => $value) {
                $selectStmt->bindValue($key, $value);
            }
            $selectStmt->execute();
            $results = $selectStmt->fetchAll(\PDO::FETCH_ASSOC);

            if(empty($results)) {
                return [
                    'status' => false,
                    'httpCode' => 409,
                    'message' => 'Invoices not found',
                    'data' => []
                ];
            }

            if (!empty($results) && (null !== $results[0]['invoice_status']) && $results[0]['invoice_status'] !== 'active') {
                return [
                    'status' => false,
                    'httpCode' => 403,
                    'message' => 'Sharing this invoice is not allowed.',
                    'data' => []
                ];
            }

            $invoices = array_map(function($row) {
                $age = $this->timestampHelper->calculateAge($row['dob'] ?? null);
                return [
                    'invoiceId'     => $row['invoice_id'],
                    'invoiceType'     => isset($row['invoice_type']) ? ucwords(strtolower($row['invoice_type'])) : null,
                    'invoiceNumber' => $row['invoice_number'],
                    'invoiceDate'   => $row['invoice_date'],
                    'name'          => $row['name'],
                    'phone'        => $row['phone'],
                    'dob'           => $row['dob'],
                    'age'           => $age,
                    'place'         => $row['place'],
                    'frame'         => $row['frame'],
                    'lence'         => $row['lence'],
                    'power' => [
                        'rSph'        => $row['r_sph'],
                        'rCyl'         => $row['r_cyl'],
                        'rAxis'        => $row['r_axis'],
                        'rVia'         => $row['r_via'],
                        'rAdd'         => $row['r_add'],
                        'rPd'          => $row['r_pd'],
                        'lSph'        => $row['l_sph'],
                        'lCyl'         => $row['l_cyl'],
                        'lAxis'        => $row['l_axis'],
                        'lVia'         => $row['l_via'],
                        'lAdd'         => $row['l_add'],
                        'lPd'          => $row['l_pd'],
                    ],
                    'amount'        => $row['amount'],
                    'offer'         => $row['offer'],
                    'claim'         => $row['claim'],
                    'remark'        => $row['remark'],
                    'paymentMode'   => isset($row['payment_mode']) ? $row['payment_mode'] : null,
                    // 'invoiceStatus' => $row['invoice_status'],
                    'createdAt'     => $row['created_at'],
                ];
            }, $results);

            return [
                'status' => true,
                'httpCode' => 200,
                'message' => 'Shared invoices fetched successfully',
                'data' => [
                    'invoices' => $invoices
                ]
            ];

        } catch (\PDOException $e) {
            return ['status' => false, 'httpCode' => 500, 'message' => 'Database error: ' . $e->getMessage()];
        } catch (\Exception $e) {
            return ['status' => false, 'httpCode' => 500, 'message' => 'Unexpected error: ' . $e->getMessage()];
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
