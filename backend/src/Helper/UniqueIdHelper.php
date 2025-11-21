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
        // Prevent invalid (too large) lengths
        if ($length <= 0 || $length > 128) {
            $length = 12; // fallback
        }

        $bytes = random_bytes((int)ceil($length / 2));
        $uniqueId = substr(bin2hex($bytes), 0, $length);

        return $prefix . strtoupper($uniqueId);
    }

    /**
     * Generate a timestamp-based unique code
     *
     * @param int|string $timestamp
     * @param int $length Length of the random part
     * @param string $prefix Optional prefix
     * @param string $separator Optional separator
     * @return string
     */
    public function generateWithTimestamp($timestamp = '', int $length = 12, string $prefix = '', string $separator = '.'): string
    {
        // Ensure timestamp is string for prefix use
        $timestampStr = (string)$timestamp;

        // Proper argument order (prefix is timestamp + separator)
        $random = $this->generate($length);

        return $prefix . strtoupper($timestampStr . $separator . $random);
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
