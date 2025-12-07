<?php

namespace App\Services\AI\Utils;

class SafeJsonExtractor
{
    /**
     * Extract the first valid JSON object from a string.
     * Useful when LLM returns text like "Here is the JSON: {...}".
     */
    public static function extractFirstJsonObject(string $text): ?array
    {
        // Non-greedy match for {...}
        // Note: This simple regex handles non-nested braces. For nested, we rely on json_decode validation.
        // We try to find the outermost braces.
        if (preg_match('/\{(?:[^{}]|(?R))*\}/s', $text, $matches)) {
            $jsonStr = $matches[0];
            $decoded = json_decode($jsonStr, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }
        
        // Fallback: try finding just the first { and last }
        $start = strpos($text, '{');
        $end = strrpos($text, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $candidate = substr($text, $start, $end - $start + 1);
            $decoded = json_decode($candidate, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }
}
