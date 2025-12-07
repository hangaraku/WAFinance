<?php

namespace App\Services\AI\Utils;

class AmountParser
{
    /**
     * Indonesian currency abbreviations and multipliers
     */
    protected static array $multipliers = [
        'rb' => 1000,
        'ribu' => 1000,
        'k' => 1000,
        'jt' => 1000000,
        'juta' => 1000000,
        'm' => 1000000,
        'mio' => 1000000,
        'miliar' => 1000000000,
        'b' => 1000000000,
        'bio' => 1000000000,
    ];

    /**
     * Currency symbols and their canonical forms
     */
    protected static array $currencySymbols = [
        'rp' => 'IDR',
        'rp.' => 'IDR',
        'idr' => 'IDR',
        '$' => 'USD',
        'usd' => 'USD',
    ];

    /**
     * Parse an amount string into a normalized numeric value.
     * Handles Indonesian slang like "5rb", "10k", "2jt", etc.
     *
     * @param string $input The raw amount string (e.g., "5rb", "Rp 10.000", "2,5jt")
     * @return array ['amount' => float, 'currency' => string, 'original' => string]
     */
    public static function parse(string $input): array
    {
        $original = $input;
        $input = trim(strtolower($input));
        $currency = 'IDR'; // Default to IDR

        // Remove common separators and normalize
        // Handle currency symbols first
        foreach (self::$currencySymbols as $symbol => $currencyCode) {
            if (str_starts_with($input, $symbol)) {
                $currency = $currencyCode;
                $input = trim(substr($input, strlen($symbol)));
                break;
            }
        }

        // Remove spaces
        $input = str_replace(' ', '', $input);

        // Handle decimal separators (Indonesian uses . for thousands and , for decimals)
        // First, detect the format
        $hasComma = strpos($input, ',') !== false;
        $hasDot = strpos($input, '.') !== false;

        if ($hasComma && $hasDot) {
            // If both exist, determine which is decimal
            $lastComma = strrpos($input, ',');
            $lastDot = strrpos($input, '.');
            
            if ($lastComma > $lastDot) {
                // Comma is decimal separator (European/Indonesian format)
                $input = str_replace('.', '', $input);
                $input = str_replace(',', '.', $input);
            } else {
                // Dot is decimal separator (US format)
                $input = str_replace(',', '', $input);
            }
        } elseif ($hasComma) {
            // Only comma - could be decimal or thousand separator
            // Check if it looks like a decimal (1-2 digits after comma at end)
            if (preg_match('/,\d{1,2}(?:[a-z]*)?$/', $input)) {
                $input = str_replace(',', '.', $input);
            } else {
                $input = str_replace(',', '', $input);
            }
        } elseif ($hasDot) {
            // Only dot - check if it looks like thousand separator (groups of 3)
            if (preg_match('/^\d{1,3}(?:\.\d{3})+(?:[a-z]*)?$/', $input)) {
                $input = str_replace('.', '', $input);
            }
            // Otherwise, treat as decimal
        }

        // Extract numeric part and multiplier suffix
        $amount = 0;
        $multiplier = 1;

        // Check for multiplier suffix
        foreach (self::$multipliers as $suffix => $mult) {
            if (str_ends_with($input, $suffix)) {
                $multiplier = $mult;
                $input = substr($input, 0, -strlen($suffix));
                break;
            }
        }

        // Parse the numeric value
        if (is_numeric($input)) {
            $amount = (float) $input * $multiplier;
        } else {
            // Try to extract any numeric value
            if (preg_match('/[\d.]+/', $input, $matches)) {
                $amount = (float) $matches[0] * $multiplier;
            }
        }

        return [
            'amount' => round($amount, 2),
            'currency' => $currency,
            'original' => $original
        ];
    }

    /**
     * Extract all amounts from a text string.
     * Useful for detecting multiple transactions in one message.
     *
     * @param string $text Full message text
     * @return array List of parsed amounts with their positions
     */
    public static function extractAmounts(string $text): array
    {
        $results = [];
        
        // Pattern to match amounts with optional currency and suffix
        // Matches: 5rb, 10k, Rp 50.000, 2,5jt, Rp50000, 100ribu, etc.
        $pattern = '/(?:rp\.?\s*)?(\d+(?:[.,]\d+)*)\s*(?:rb|ribu|k|jt|juta|m|mio|miliar|b|bio)?/i';
        
        if (preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $index => $match) {
                $parsed = self::parse($match[0]);
                if ($parsed['amount'] > 0) {
                    $results[] = [
                        'amount' => $parsed['amount'],
                        'currency' => $parsed['currency'],
                        'original' => $match[0],
                        'position' => $match[1]
                    ];
                }
            }
        }

        return $results;
    }

    /**
     * Format an amount for display in Indonesian format.
     *
     * @param float $amount The amount to format
     * @param string $currency The currency code
     * @return string Formatted amount string
     */
    public static function format(float $amount, string $currency = 'IDR'): string
    {
        if ($currency === 'IDR') {
            return 'Rp ' . number_format($amount, 0, ',', '.');
        }
        
        return $currency . ' ' . number_format($amount, 2, '.', ',');
    }
}
