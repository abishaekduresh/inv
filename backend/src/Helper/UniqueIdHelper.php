<?php
namespace App\Helper;

class UniqueIdHelper
{
    /**
     * Generate a random unique code
     *
     * @param int $length Length of the code (excluding prefix)
     * @param string $prefix Optional prefix (e.g., "USR-", "INV-")
     * @return string
     */
    public function generate(int $length = 12, string $prefix = ''): string
    {
        $bytes = random_bytes(ceil($length / 2));
        $uniqueId = substr(bin2hex($bytes), 0, $length);

        return $prefix . strtoupper($uniqueId);
    }

    /**
     * Generate a timestamp-based unique code
     *
     * @param string $prefix
     * @return string
     */
    public function generateWithTimestamp(string $prefix = ''): string
    {
        return $prefix . strtoupper(uniqid('', true));
    }

    /**
     * Generate UUID v4
     *
     * @return string
     */
    public function generateUuidV4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40); // Version 4
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80); // Variant

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
