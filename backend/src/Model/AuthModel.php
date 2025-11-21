<?php
namespace App\Model;

use PDO;
use PDOException;
use App\Helper\TimestampHelper;
use App\Helper\PasswordHelper;
use App\Helper\DatabaseHelper;
use App\Helper\JwtHelper;

class AuthModel
{
    private $pdo;
    private TimestampHelper $timestampHelper;
    private PasswordHelper $passwordHelper;

    public function __construct()
    {
        $this->pdo             = new DatabaseHelper();
        $this->timestampHelper = new TimestampHelper();
        $this->passwordHelper  = new PasswordHelper();
    }

    /**
     * Authenticate user by phone and password
     */
    public function loginUser(array $data): array
    {
        $conn = $this->pdo->getConnection();

        try {
            // Find user by phone
            $stmt = $conn->prepare("SELECT * FROM users WHERE phone = :phone LIMIT 1");
            $stmt->bindParam(':phone', $data['phone'], PDO::PARAM_STR);
            $stmt->execute();

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                return [
                    'status'   => false,
                    'message'  => 'Invalid phone or password',
                    'httpCode' => 401, // Unauthorized
                ];
            }

            if ($user['status'] === 'inactive') {
                return [
                    'status'   => false,
                    'message'  => 'Access denied: Inactive',
                    'httpCode' => 401, // Unauthorized
                ];
            }

            if ($user['status'] === 'deleted') {
                return [
                    'status'   => false,
                    'message'  => 'Access denied: Deleted',
                    'httpCode' => 401, // Unauthorized
                ];
            }

            // Verify password
            if (!$this->passwordHelper->verify($data['password'], $user['password'])) {
                return [
                    'status'   => false,
                    'message'  => 'Invalid phone or password',
                    'httpCode' => 401,
                ];
            }
            
            $jwtHelper = new JwtHelper($_ENV['JWT_SECRET'], $_ENV['JWT_ALGO'], $_ENV['JWT_EXPIRY']);
            $token = $jwtHelper->generateToken([
                "sub"  => $user['user_id'],
                "busid" => $user['business_id'] ?? null
            ]);

            // Successful login
            return [
                'status'   => true,
                'message'  => 'Login successful',
                'httpCode' => 200,
                'data'     => [
                    'userId' => $user['user_id'],
                    'name'   => $user['name'],
                ],
                'jwtToken'  => $token,
            ];

        } catch (PDOException $e) {
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
