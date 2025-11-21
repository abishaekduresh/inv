<?php
namespace App\Model;

use PDO;
use PDOException;
use App\Helper\TimestampHelper;
use App\Helper\PasswordHelper;
use App\Helper\UniqueIdHelper;
use App\Helper\DatabaseHelper;

class UserModel
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

    public function getUser(array $data)
    {
        $conn = $this->pdo->getConnection();
        try {
            $searchQuery = $data['searchQuery'] ?? null;
            $userId = $data['userId'] ?? null;
            $status = $data['status'] ?? null;
            $page = max(1, (int)($data['page'] ?? 1));
            $limit = (int)($data['limit'] ?? 25);
            $offset = ($page - 1) * $limit;

            // Base queries
            $countQuery = "SELECT COUNT(*) FROM users WHERE 1=1";
            $selectQuery = "SELECT * FROM users WHERE 1=1";
            $params = [];

            // Dynamic filters
            if (!empty($searchQuery)) {
                $countQuery .= " AND (name LIKE :name OR phone LIKE :phone)";
                $selectQuery .= " AND (name LIKE :name OR phone LIKE :phone)";
                $params[':name'] = trim($searchQuery) . '%';
                $params[':phone'] = trim($searchQuery) . '%';
            }
            if (!empty($userId)) {
                $countQuery .= " AND user_id = :userId";
                $selectQuery .= " AND user_id = :userId";
                $params[':userId'] = trim($userId);
            }
            if (!empty($status)) {
                $countQuery .= " AND status = :status";
                $selectQuery .= " AND status = :status";
                $params[':status'] = trim($status);
            } else {
                $countQuery .= " AND status != :status";
                $selectQuery .= " AND status != :status";
                $params[':status'] = 'deleted';
            }

            // Count total records
            $countStmt = $conn->prepare($countQuery);
            foreach ($params as $key => $value) { $countStmt->bindValue($key, $value); }
            $countStmt->execute();
            $totalRecords = (int)$countStmt->fetchColumn();

            if ($totalRecords === 0) {
                return [
                    'status' => false,
                    'httpCode' => 409,
                    'message' => 'No user records found matching the criteria.',
                    'data' => null
                ];
            }

            $selectQuery .= " ORDER BY id DESC";

            // Apply LIMIT only if limit > 0
            if ($limit > 0) {
                $selectQuery .= " LIMIT :offset, :limit";
            }

            $selectStmt = $conn->prepare($selectQuery);
            foreach ($params as $key => $value) { $selectStmt->bindValue($key, $value); }

            if ($limit > 0) {
                $selectStmt->bindValue(':offset', (int)$offset, \PDO::PARAM_INT);
                $selectStmt->bindValue(':limit', (int)$limit, \PDO::PARAM_INT);
            }

            $selectStmt->execute();
            $results = $selectStmt->fetchAll(\PDO::FETCH_ASSOC);

            $results = array_map(function($row) {
                return [
                    'userId'    => $row['user_id'],
                    'name'      => $row['name'] ?? null,
                    'phone'     => $row['phone'] ?? null,
                    'role'      => $row['role'] ?? null,
                    'status'    => $row['status'] ?? null,
                    'createdAt' => $row['created_at'] ?? null
                    // add any other fields you need
                ];
            }, $results);

            // Calculate total pages (1 if limit=0)
            $totalPages = $limit > 0 ? ceil($totalRecords / $limit) : 1;

            return [
                'status' => true,
                'httpCode' => 200,
                'message' => !empty($results) ? 'User records fetched successfully.' : 'No record found.',
                'data' => [
                    'users' => $results
                ],
                'pagination' => [
                    'currentPage' => $page,
                    'limit' => $limit,
                    'totalPages' => $totalPages,
                    'totalRecords' => $totalRecords
                ]
            ];

        } catch (\PDOException $e) {
            return ['status' => false, 'httpCode' => 500, 'message' => 'Database error: ' . $e->getMessage()];
        } catch (\Exception $e) {
            return ['status' => false, 'httpCode' => 500, 'message' => 'An unexpected error occurred: ' . $e->getMessage()];
        } finally {
            $this->pdo->disconnect();
        }
    }

    public function createUser(array $data): array
    {
        $conn = $this->pdo->getConnection();
        $conn->beginTransaction(); // Start transaction

        try {
            $uniqueId = $this->uniqueIdHelper->generate(8, '');

            // Single query to check conflicts
            $checkStmt = $conn->prepare("
                SELECT user_id, status 
                FROM users 
                WHERE user_id = :user_id OR phone = :phone
            ");
            $checkStmt->execute([
                ':user_id' => $uniqueId,
                ':phone'   => $data['phone'],
            ]);

            $rows = $checkStmt->fetchAll(PDO::FETCH_ASSOC);

            if ($rows) {
                foreach ($rows as $row) {
                    // Case 1: Block if user_id already exists
                    if ($row['user_id'] === $uniqueId) {
                        $conn->rollBack();
                        return [
                            'status'   => false,
                            'message'  => 'User ID already exists',
                            'httpCode' => 409,
                        ];
                    }

                    // Case 2: Block if phone exists with active/inactive
                    if ((int)$data['phone'] && in_array($row['status'], ['active', 'inactive'])) {
                        $conn->rollBack();
                        return [
                            'status'   => false,
                            'message'  => 'Phone already exists',
                            'httpCode' => 409,
                        ];
                    }
                }

                // If we reach here → only deleted records exist → allow insert
            }

            // Hash password
            $hashedPassword = $this->passwordHelper->hash($data['password']);
            // Current timestamp
            $createdAt = $this->timestampHelper->getCurrentUnixTimestamp();
            // Insert user
            $stmt = $conn->prepare("
                INSERT INTO users (user_id, name, phone, role, password, status, created_at)
                VALUES (:user_id, :name, :phone, :role, :password, :status, :created_at)
            ");
            $inserted = $stmt->execute([
                ':user_id'   => $uniqueId,
                ':name'     => $data['name'],
                ':phone'    => $data['phone'],
                ':role'     => $data['role'],
                ':password' => $hashedPassword,
                ':status'   => 'active',
                ':created_at'=> $createdAt,
            ]);

            if (!$inserted) {
                $conn->rollBack();
                return [
                    'status'   => false,
                    'message'  => 'Failed to create user',
                    'httpCode' => 500,
                ];
            }

            $lastInsertId = (int) $conn->lastInsertId();
            $conn->commit();

            return [
                'status'   => true,
                'message'  => 'User created successfully',
                'httpCode' => 201,
                'data'     => [
                    // 'id'     => $lastInsertId,
                    'userId' => $uniqueId,
                    'name'   => $data['name'],
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

    public function updateUser(string $userId, array $data): array
    {
        $conn = $this->pdo->getConnection();
        $conn->beginTransaction(); // Start transaction
        $reqUserId = $data['reqUserId'] ?? null;

        try {
            // Check if the user exists
            $checkUserStmt = $conn->prepare("SELECT * FROM users WHERE user_id = :user_id LIMIT 1");
            $checkUserStmt->execute([':user_id' => $userId]);
            $existingUser = $checkUserStmt->fetch(PDO::FETCH_ASSOC);

            if (!$existingUser) {
                $conn->rollBack();
                return [
                    'status'   => false,
                    'message'  => 'User not found',
                    'httpCode' => 200,
                ];
            }

            if($existingUser['status'] == 'deleted') {
                $conn->rollBack();
                return [
                    'status'   => false,
                    'message'  => 'Deleted user cannot be updated',
                    'httpCode' => 400,
                ];
            }

            if($existingUser['status'] == 'inactive' && $data['status'] != 'active') {
                $conn->rollBack();
                return [
                    'status'   => false,
                    'message'  => 'Inactive user cannot be updated',
                    'httpCode' => 400,
                ];
            }

            if(isset($data['status']) && $reqUserId === $userId && $existingUser['status'] === 'active' && $data['status'] !== 'active') {
                $conn->rollBack();
                return [
                    'status'   => false,
                    'message'  => 'Users cannot change their own status to inactive',
                    'httpCode' => 403,
                ];
            }

            // Check if the phone number is already used by another user
            if (isset($data['phone'])) {
                $checkPhoneStmt = $conn->prepare("
                    SELECT user_id FROM users WHERE phone = :phone AND user_id != :user_id LIMIT 1
                ");
                $checkPhoneStmt->execute([
                    ':phone'   => $data['phone'],
                    ':user_id' => $userId,
                ]);

                $existingPhone = $checkPhoneStmt->fetch(PDO::FETCH_ASSOC);
                if ($existingPhone) {
                    $conn->rollBack();
                    return [
                        'status'   => false,
                        'message'  => 'Phone already exists',
                        'httpCode' => 409,
                    ];
                }
            }

            // Prepare update fields
            $fields = [];
            $params = [':user_id' => $userId];

            if (isset($data['name'])) {
                $fields[] = 'name = :name';
                $params[':name'] = $data['name'];
            }

            if (isset($data['phone'])) {
                $fields[] = 'phone = :phone';
                $params[':phone'] = $data['phone'];
            }

            if (isset($data['role'])) {
                $fields[] = 'role = :role';
                $params[':role'] = $data['role'];
            }

            if (isset($data['status'])) {
                $fields[] = 'status = :status';
                $params[':status'] = $data['status'];
            }

            if (isset($data['password'])) {
                $fields[] = 'password = :password';
                $params[':password'] = $this->passwordHelper->hash($data['password']);
            }

            if (empty($fields)) {
                $conn->rollBack();
                return [
                    'status'   => false,
                    'message'  => 'No fields to update',
                    'httpCode' => 400,
                ];
            }

            $updateStmt = $conn->prepare("
                UPDATE users
                SET " . implode(', ', $fields) . "
                WHERE user_id = :user_id
            ");

            $updated = $updateStmt->execute($params);

            if (!$updated) {
                $conn->rollBack();
                return [
                    'status'   => false,
                    'message'  => 'Failed to update user',
                    'httpCode' => 500,
                ];
            }

            $conn->commit();

            return [
                'status'   => true,
                'message'  => 'User updated successfully',
                'httpCode' => 200,
                'data'     => [
                    'userId' => $userId,
                    'name'   => $data['name'] ?? $existingUser['name'],
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

    public function deleteUser($data): array
    {
        $conn = $this->pdo->getConnection();
        $conn->beginTransaction();
        $userId = $data['userId'] ?? null;
        $reqUserId = $data['reqUserId'] ?? null;

         if (empty($userId)) {
            return [
                'status'   => false,
                'message'  => 'User ID is required',
                'httpCode' => 400,
            ];
        }
        
        try {
            // Check if the user exists
            $checkUserStmt = $conn->prepare("SELECT * FROM users WHERE user_id = :user_id LIMIT 1");
            $checkUserStmt->execute([':user_id' => $userId]);
            $existingUser = $checkUserStmt->fetch(PDO::FETCH_ASSOC);

            if (!$existingUser) {
                $conn->rollBack();
                return [
                    'status'   => false,
                    'message'  => 'User not found',
                    'httpCode' => 409,
                ];
            }

            if($existingUser['status'] === 'deleted') {
                $conn->rollBack();
                return [
                    'status'   => false,
                    'message'  => 'User already deleted',
                    'httpCode' => 400,
                ];
            }

            if($reqUserId === $existingUser['user_id']) {
                $conn->rollBack();
                return [
                    'status'   => false,
                    'message'  => 'Users cannot delete their own account',
                    'httpCode' => 400,
                ];
            }

            // Always mark status = 'Deleted' for soft delete
            $updateStmt = $conn->prepare("
                UPDATE users
                SET status = :status
                WHERE user_id = :user_id
            ");
            $deleted = $updateStmt->execute([
                ':status' => 'deleted',
                ':user_id' => $userId,
            ]);

            if (!$deleted) {
                $conn->rollBack();
                return [
                    'status'   => false,
                    'message'  => 'Failed to delete user',
                    'httpCode' => 500,
                ];
            }

            $conn->commit();

            return [
                'status'   => true,
                'message'  => 'User deleted successfully',
                'httpCode' => 200,
                'data'     => [
                    'userId' => $userId,
                    'name'   => $existingUser['name'],
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

}
