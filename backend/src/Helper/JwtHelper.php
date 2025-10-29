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
            'jti' => $this->uniqueIdHelper->generate(12, ''),
            'exp' => $unixTimestamp + $this->ttl,
        ]);
        return JWT::encode($payload, $this->secret, $this->algo);
    }

    /**
     * Verify and decode token
     */
    public function verifyToken(string $jwt): ?object
    {
        try {
            return JWT::decode($jwt, new Key($this->secret, $this->algo));
        } catch (Exception $e) {
            return null; // invalid/expired
        }
    }
}
