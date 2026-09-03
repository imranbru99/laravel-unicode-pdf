<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Unicode;

use ImranDev\UnicodePdf\Exceptions\InvalidUtf8Exception;

class Utf8Validator
{
    /**
     * Validate whether a string is valid UTF-8.
     * Throws InvalidUtf8Exception if invalid and $throw is true.
     *
     * @throws InvalidUtf8Exception
     */
    public function validate(string $string, bool $throw = true): bool
    {
        if (mb_check_encoding($string, 'UTF-8')) {
            return true;
        }

        if (! $throw) {
            return false;
        }

        $offset = $this->findInvalidByteOffset($string);
        $context = $this->extractContextSnippet($string, $offset);

        throw InvalidUtf8Exception::atOffset($offset, $context);
    }

    /**
     * Find the exact byte index of the first invalid UTF-8 byte.
     */
    public function findInvalidByteOffset(string $string): int
    {
        $len = strlen($string);
        $i = 0;

        while ($i < $len) {
            $byte = ord($string[$i]);

            // 1-byte ASCII (0x00 - 0x7F)
            if ($byte <= 0x7F) {
                $i++;

                continue;
            }

            // 2-byte sequence (0xC2 - 0xDF)
            if ($byte >= 0xC2 && $byte <= 0xDF) {
                if ($i + 1 >= $len || (ord($string[$i + 1]) & 0xC0) !== 0x80) {
                    return $i;
                }
                $i += 2;

                continue;
            }

            // 3-byte sequence (0xE0 - 0xEF)
            if ($byte >= 0xE0 && $byte <= 0xEF) {
                if ($i + 2 >= $len) {
                    return $i;
                }
                $b2 = ord($string[$i + 1]);
                $b3 = ord($string[$i + 2]);

                if (($b2 & 0xC0) !== 0x80 || ($b3 & 0xC0) !== 0x80) {
                    return $i;
                }

                // Check overlong and surrogate ranges
                if ($byte === 0xE0 && $b2 < 0xA0) {
                    return $i; // overlong
                }
                if ($byte === 0xED && $b2 > 0x9F) {
                    return $i; // surrogate
                }

                $i += 3;

                continue;
            }

            // 4-byte sequence (0xF0 - 0xF4)
            if ($byte >= 0xF0 && $byte <= 0xF4) {
                if ($i + 3 >= $len) {
                    return $i;
                }
                $b2 = ord($string[$i + 1]);
                $b3 = ord($string[$i + 2]);
                $b4 = ord($string[$i + 3]);

                if (($b2 & 0xC0) !== 0x80 || ($b3 & 0xC0) !== 0x80 || ($b4 & 0xC0) !== 0x80) {
                    return $i;
                }

                if ($byte === 0xF0 && $b2 < 0x90) {
                    return $i; // overlong
                }
                if ($byte === 0xF4 && $b2 > 0x8F) {
                    return $i; // > U+10FFFF
                }

                $i += 4;

                continue;
            }

            // Invalid leading byte
            return $i;
        }

        return -1;
    }

    /**
     * Extract a safe printable snippet around the error byte offset.
     */
    protected function extractContextSnippet(string $string, int $offset): string
    {
        if ($offset < 0) {
            return '';
        }

        $start = max(0, $offset - 15);
        $len = min(30, strlen($string) - $start);
        $raw = substr($string, $start, $len);

        // Replace non-printable / broken bytes with hex representation
        $result = '';
        for ($j = 0; $j < strlen($raw); $j++) {
            $c = $raw[$j];
            $ord = ord($c);
            if ($ord >= 32 && $ord <= 126) {
                $result .= $c;
            } else {
                $result .= sprintf('\\x%02X', $ord);
            }
        }

        return $result;
    }
}
