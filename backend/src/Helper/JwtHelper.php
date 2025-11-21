<?php
namespace App\Helper;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Helper\TimestampHelper;
use Exception;
use App\Helper\UniqueIdHelper;

class JwtHelper
{
    private string $secret;
    private string $algo;
    private int $ttl; // token lifetime in seconds
    private TimestampHelper $timestampHelper;
    private UniqueIdHelper $uniqueIdHelper;

    public function __construct(
        string $secret,
        string $algo = 'HS256',
        int $ttl = 3600 // default 1h
    ) {
        $this->secret = $secret;
        $this->algo   = $algo;
        $this->ttl    = $ttl;
        $this->timestampHelper = new TimestampHelper();
        $this->uniqueIdHelper  = new UniqueIdHelper();
    }

    /**
     * Generate JWT token
     */
    public function generateToken(array $claims): string
    {
        $unixTimestamp = $this->timestampHelper->getCurrentUnixTimestamp();
        $payload = array_merge($claims, [
            'iat' => $unixTimestamp,
            'nbf' => $unixTimestamp,
            'jti' => $this->uniqueIdHelper->generate(8, ''),
            'exp' => $unixTimestamp + $this->ttl,
        ]);
        return JWT::encode($payload, $this->secret, $this->algo);
    }

    /**
     * Verify and decode token
     */
    public function verifyToken(string $jwt): array
    {
        try {
            $decoded = JWT::decode($jwt, new Key($this->secret, $this->algo));

            return [
                'status'  => true,
                'message' => 'Token is valid.',
                'data'    => [
                    'jwt'   => $decoded ?? null,
                    'userId'     => $decoded->sub    ?? null,
                    'businessId' => $decoded->busid  ?? null,
                ],
                'httpCode' => 200,
            ];
        } catch (\Firebase\JWT\ExpiredException $e) {
            return [
                'status'  => false,
                'message' => 'Token has expired.',
                'help'    => 'Please refresh your session or log in again.',
                'error'   => $e->getMessage(),
                'httpCode' => 401,
            ];
        } catch (\Firebase\JWT\SignatureInvalidException $e) {
            return [
                'status'  => false,
                'message' => 'Invalid token signature.',
                'help'    => 'Your token signature does not match the expected key. Ensure your key is correct.',
                'error'   => $e->getMessage(),
                'httpCode' => 401,
            ];
        } catch (\Firebase\JWT\BeforeValidException $e) {
            return [
                'status'  => false,
                'message' => 'Token not valid yet.',
                'help'    => 'This token is not active yet (check the nbf or iat fields).',
                'error'   => $e->getMessage(),
                'httpCode' => 401,
            ];
        } catch (\UnexpectedValueException $e) {
            return [
                'status'  => false,
                'message' => 'Malformed token.',
                'help'    => 'The token structure is invalid. It might be corrupted or incomplete.',
                'error'   => $e->getMessage(),
                'httpCode' => 400,
            ];
        } catch (\Throwable $e) {
            return [
                'status'  => false,
                'message' => 'Token verification failed.',
                'help'    => 'Unexpected error while decoding token. Ensure it was generated with the same secret and algorithm.',
                'error'   => $e->getMessage(),
                'httpCode' => 500,
            ];
        }
    }

}
