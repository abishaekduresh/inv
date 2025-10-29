<?php
namespace App\Helper;

class TimestampHelper
{
    private \DateTimeZone $timezone;

    public function __construct()
    {
        $this->timezone = new \DateTimeZone($_ENV['TZ'] = 'Asia/Kolkata');
    }

    /**
     * Get the current timestamp in the specified format.
     * 
     * @param string $format Date and time format (default: 'YmdHis')
     * @return string
     */
    public function getCurrentTimestamp(string $format = 'YmdHis', ?string $modifyInterval = null): string
    {
        $now = new \DateTime('now', $this->timezone);

        if ($modifyInterval) {
            $now->modify($modifyInterval); // e.g., '+1 hour', '-30 minutes', '+2 days'
        }

        return $now->format($format);
    }

    /**
     * Get a formatted timestamp from a given timestamp.
     * 
     * @param string|int $timestamp The timestamp (string or Unix timestamp)
     * @param string $format Date and time format (default: 'YmdHis')
     * @return string
     * @throws \Exception If the timestamp is invalid
     */
    public function getFormattedTimestamp($timestamp, string $format = 'YmdHis'): string
    {
        if (is_numeric($timestamp)) {
            $datetime = new \DateTime("@$timestamp");
            $datetime->setTimezone($this->timezone);
        } else {
            $datetime = new \DateTime($timestamp, $this->timezone);
        }

        return $datetime->format($format);
    }

    /**
     * Get the current timestamp in ISO 8601 format.
     */
    public function getCurrentIso8601Timestamp(?string $modifyInterval = null): string
    {
        $now = new \DateTime('now', $this->timezone);

        if ($modifyInterval) {
            $now->modify($modifyInterval);
        }

        return $now->format(DATE_ATOM);
    }

    /**
     * Get current Unix timestamp.
     * 
     * @return int
     */
    public function getCurrentUnixTimestamp(?string $modifyInterval = null): int
    {
        $now = new \DateTime('now', $this->timezone);

        if ($modifyInterval) {
            $now->modify($modifyInterval);
        }

        return $now->getTimestamp();
    }

    /**
     * Get Unix timestamp from given string or timestamp.
     * 
     * @param string|int $time
     * @return int
     */
    public function timestampToUnix($time): int
    {
        if (is_numeric($time)) {
            return (int) $time;
        }

        $datetime = new \DateTime($time, $this->timezone);
        return $datetime->getTimestamp();
    }

    /**
     * Convert Unix timestamp to formatted date string.
     *
     * @param int $unixTimestamp
     * @param string $format
     * @return string
     */
    public function unixToTimestamp(int $unixTimestamp, string $format = 'Y-m-d H:i:s'): string
    {
        $datetime = new \DateTime("@$unixTimestamp");
        $datetime->setTimezone($this->timezone);
        return $datetime->format($format);
    }

    /**
     * Get Unix timestamp X seconds in the future from now.
     *
     * @param int $seconds Number of seconds to add
     * @return int
     */
    public function getFutureUnixTimestamp(int $seconds): int
    {
        $now = new \DateTime('now', $this->timezone);
        $now->modify("+$seconds seconds");
        return $now->getTimestamp();
    }

    public function convertUTCToTimezone(string $utcTime, string $format = 'Y-m-d H:i:s'): string
    {
        $utcDateTime = new \DateTime($utcTime, new \DateTimeZone('UTC'));
        $utcDateTime->setTimezone($this->timezone);
        return $utcDateTime->format($format);
    }

    /**
     * Calculate age from a given DOB and optionally a "till date".
     *
     * @param string|int|null $dob       Date of birth ('Y-m-d', 'Y-m-d H:i:s', or Unix timestamp)
     * @param string|int|null $tillDate  Optional date to calculate until ('Y-m-d' or timestamp)
     * @return int|null Age in years, or null if invalid
     */
    public function calculateAge($dob, $tillDate = null): ?int
    {
        if (empty($dob)) {
            return null; // No DOB given
        }

        try {
            // Convert DOB to DateTime
            $dobDate = is_numeric($dob)
                ? (new \DateTime("@$dob"))->setTimezone($this->timezone)
                : new \DateTime($dob, $this->timezone);

            // Determine end date (either tillDate or now)
            if (!empty($tillDate)) {
                $endDate = is_numeric($tillDate)
                    ? (new \DateTime("@$tillDate"))->setTimezone($this->timezone)
                    : new \DateTime($tillDate, $this->timezone);
            } else {
                $endDate = new \DateTime('now', $this->timezone);
            }

            // Ensure till date is after DOB
            if ($endDate < $dobDate) {
                return 0; // Optional: could also return null or negative years
            }

            // Calculate difference in full years
            return $dobDate->diff($endDate)->y;

        } catch (\Exception $e) {
            return null; // Invalid date format
        }
    }

}