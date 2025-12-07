<?php

namespace App\Services\AI\Utils;

use Carbon\Carbon;

class DateParser
{
    /**
     * Parse a natural language date string into a normalized format.
     * Returns ['type' => 'date'|'month'|'year', 'value' => 'Y-m-d'|'Y-m'|'Y'].
     * Falls back to returning the original string if parsing fails.
     */
    public static function parse(string $input): array
    {
        $input = trim(strtolower($input));
        $now = Carbon::now();

        // 1. Relative days
        if (in_array($input, ['hari ini', 'today'])) {
            return ['type' => 'date', 'value' => $now->format('Y-m-d')];
        }
        if (in_array($input, ['kemarin', 'yesterday'])) {
            return ['type' => 'date', 'value' => $now->copy()->subDay()->format('Y-m-d')];
        }
        if (preg_match('/^(\d+)\s*(?:hari lalu|days ago)$/', $input, $matches)) {
            return ['type' => 'date', 'value' => $now->copy()->subDays((int)$matches[1])->format('Y-m-d')];
        }

        // 2. Relative months
        if (in_array($input, ['bulan ini', 'this month'])) {
            return ['type' => 'month', 'value' => $now->format('Y-m')];
        }
        if (in_array($input, ['bulan lalu', 'last month'])) {
            return ['type' => 'month', 'value' => $now->copy()->subMonth()->format('Y-m')];
        }

        // 3. Relative years
        if (in_array($input, ['tahun ini', 'this year'])) {
            return ['type' => 'year', 'value' => $now->format('Y')];
        }
        if (in_array($input, ['tahun lalu', 'last year'])) {
            return ['type' => 'year', 'value' => $now->copy()->subYear()->format('Y')];
        }

        // 4. Explicit formats
        // Try Y-m-d
        try {
            $d = Carbon::createFromFormat('Y-m-d', $input);
            return ['type' => 'date', 'value' => $d->format('Y-m-d')];
        } catch (\Exception $e) {}

        // Try Y-m
        try {
            $d = Carbon::createFromFormat('Y-m', $input);
            return ['type' => 'month', 'value' => $d->format('Y-m')];
        } catch (\Exception $e) {}

        // Try Month Name Year (e.g., "November 2025", "Nov 2025")
        // English and Indonesian month names often overlap or are handled by Carbon if locale set,
        // but we can try standard formats.
        $formats = ['F Y', 'M Y', 'F, Y', 'M, Y'];
        foreach ($formats as $fmt) {
            try {
                $d = Carbon::createFromFormat($fmt, $input);
                return ['type' => 'month', 'value' => $d->format('Y-m')];
            } catch (\Exception $e) {}
        }

        // Fallback: return as-is (let the caller handle or fail)
        return ['type' => 'raw', 'value' => $input];
    }
}
