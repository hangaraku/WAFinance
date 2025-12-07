<?php

namespace App\Services\AI;

use App\Models\User;
use App\Models\Account;
use App\Services\AI\Utils\AmountParser;
use App\Services\AI\Utils\CategoryMatcher;
use Carbon\Carbon;

class TransactionDetector
{
    /**
     * Detect transactions from a user message.
     * This parses the message locally to extract potential transactions
     * without calling external AI APIs.
     *
     * @param User $user The user context
     * @param string $message The raw message text
     * @param string $timestamp ISO 8601 timestamp from chat context
     * @return array Detected transactions and metadata
     */
    public static function detect(User $user, string $message, string $timestamp): array
    {
        $baseTime = Carbon::parse($timestamp);
        $normalizedMessage = self::normalizeMessage($message);
        
        // Split message into potential transaction segments
        $segments = self::splitIntoSegments($normalizedMessage);
        
        $detectedTransactions = [];
        $ambiguities = [];
        
        foreach ($segments as $segment) {
            $result = self::parseSegment($user, $segment['text'], $baseTime, $segment['time_hint']);
            
            if ($result['transaction']) {
                $detectedTransactions[] = $result['transaction'];
            }
            
            if (!empty($result['ambiguities'])) {
                $ambiguities = array_merge($ambiguities, $result['ambiguities']);
            }
        }
        
        return [
            'transactions' => $detectedTransactions,
            'count' => count($detectedTransactions),
            'ambiguities' => $ambiguities,
            'needs_confirmation' => count($detectedTransactions) > 0,
            'message' => self::buildConfirmationMessage($detectedTransactions, $ambiguities),
        ];
    }

    /**
     * Normalize message text for parsing.
     */
    protected static function normalizeMessage(string $message): string
    {
        // Convert to lowercase for matching
        $message = strtolower($message);
        
        // Normalize common abbreviations
        $replacements = [
            'td' => 'hari ini',
            'hr ini' => 'hari ini',
            'kmrn' => 'kemarin',
            'kmrin' => 'kemarin',
            'pg' => 'pagi',
            'sng' => 'siang',
            'sor' => 'sore',
            'mlm' => 'malam',
        ];
        
        foreach ($replacements as $abbr => $full) {
            // Only replace if it's a word boundary
            $message = preg_replace('/\b' . preg_quote($abbr, '/') . '\b/', $full, $message);
        }
        
        return $message;
    }

    /**
     * Split message into segments that might each contain a transaction.
     */
    protected static function splitIntoSegments(string $message): array
    {
        $segments = [];
        
        // Split by common separators: "dan", "terus", ",", "&"
        $parts = preg_split('/\s*(?:,\s*dan|dan|terus|kemudian|lalu|\+|&|;)\s*/i', $message);
        
        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part)) continue;
            
            // Detect time hints in segment
            $timeHint = self::extractTimeHint($part);
            
            $segments[] = [
                'text' => $part,
                'time_hint' => $timeHint
            ];
        }
        
        return $segments;
    }

    /**
     * Extract time hint from text segment.
     */
    protected static function extractTimeHint(string $text): ?string
    {
        $timeHints = [
            'pagi' => 'morning',
            'siang' => 'afternoon',
            'sore' => 'evening',
            'malam' => 'night',
            'subuh' => 'dawn',
            'morning' => 'morning',
            'afternoon' => 'afternoon',
            'evening' => 'evening',
            'night' => 'night',
        ];
        
        foreach ($timeHints as $keyword => $hint) {
            if (str_contains($text, $keyword)) {
                return $hint;
            }
        }
        
        return null;
    }

    /**
     * Parse a single segment to extract transaction details.
     */
    protected static function parseSegment(User $user, string $text, Carbon $baseTime, ?string $timeHint): array
    {
        $transaction = null;
        $ambiguities = [];
        
        // Extract amounts
        $amounts = AmountParser::extractAmounts($text);
        
        if (empty($amounts)) {
            return ['transaction' => null, 'ambiguities' => []];
        }
        
        // Use the first/primary amount found
        $amount = $amounts[0]['amount'];
        $currency = $amounts[0]['currency'];
        
        // Detect transaction type
        $type = CategoryMatcher::detectType($text);
        
        // Match category
        $category = CategoryMatcher::match($user, $text, $type);
        
        if (!$category) {
            $ambiguities[] = [
                'type' => 'category',
                'message' => "Tidak dapat menentukan kategori untuk: \"$text\""
            ];
        }
        
        // Get default account
        $account = Account::where('user_id', $user->id)
            ->where(function ($q) {
                $q->where('is_default', true)->orWhere('is_active', true);
            })
            ->orderBy('is_default', 'desc')
            ->first();
        
        if (!$account) {
            $ambiguities[] = [
                'type' => 'account',
                'message' => 'Tidak ada akun yang tersedia. Silakan buat akun terlebih dahulu.'
            ];
        }
        
        // Calculate transaction date/time based on hints
        $transactionDateTime = self::calculateDateTime($baseTime, $timeHint, $text);
        
        // Build description from text (remove amount)
        $description = self::buildDescription($text, $amounts[0]['original']);
        
        if ($amount > 0 && $category && $account) {
            $transaction = [
                'type' => $type,
                'amount' => $amount,
                'currency' => $currency,
                'category_id' => $category['id'],
                'category_name' => $category['name'],
                'account_id' => $account->id,
                'account_name' => $account->name,
                'description' => $description,
                'date' => $transactionDateTime->format('Y-m-d'),
                'time' => $transactionDateTime->format('H:i:s'),
                'time_hint' => $timeHint,
                'original_text' => $text,
            ];
        }
        
        return [
            'transaction' => $transaction,
            'ambiguities' => $ambiguities
        ];
    }

    /**
     * Calculate transaction date/time based on base time and hints.
     */
    protected static function calculateDateTime(Carbon $baseTime, ?string $timeHint, string $text): Carbon
    {
        $dateTime = $baseTime->copy();
        
        // Check for relative date references
        if (str_contains($text, 'kemarin') || str_contains($text, 'yesterday')) {
            $dateTime->subDay();
        } elseif (preg_match('/(\d+)\s*hari\s*(lalu|yang lalu)/i', $text, $matches)) {
            $dateTime->subDays((int) $matches[1]);
        }
        
        // Adjust time based on hints
        if ($timeHint) {
            switch ($timeHint) {
                case 'dawn':
                    $dateTime->setTime(5, 0, 0);
                    break;
                case 'morning':
                    $dateTime->setTime(9, 0, 0);
                    break;
                case 'afternoon':
                    $dateTime->setTime(13, 0, 0);
                    break;
                case 'evening':
                    $dateTime->setTime(17, 0, 0);
                    break;
                case 'night':
                    $dateTime->setTime(20, 0, 0);
                    break;
            }
        }
        
        return $dateTime;
    }

    /**
     * Build a clean description from text.
     */
    protected static function buildDescription(string $text, string $amountOriginal): string
    {
        // Remove the amount from description
        $description = str_replace($amountOriginal, '', $text);
        
        // Remove common prefixes/suffixes
        $removePatterns = [
            '/^(beli|bayar|buat|aku|gue|gw|saya)\s+/i',
            '/\s*(hari ini|kemarin|pagi|siang|sore|malam)\s*/i',
            '/\s*(td|tadi)\s*/i',
        ];
        
        foreach ($removePatterns as $pattern) {
            $description = preg_replace($pattern, ' ', $description);
        }
        
        $description = trim(preg_replace('/\s+/', ' ', $description));
        
        // Capitalize first letter
        return ucfirst($description);
    }

    /**
     * Build confirmation message for detected transactions.
     */
    protected static function buildConfirmationMessage(array $transactions, array $ambiguities): string
    {
        if (empty($transactions)) {
            if (!empty($ambiguities)) {
                return "Saya mendeteksi transaksi tapi ada beberapa hal yang perlu diklarifikasi:\n" .
                    implode("\n", array_map(fn($a) => "- " . $a['message'], $ambiguities));
            }
            return "Maaf, saya tidak dapat mendeteksi transaksi dari pesan Anda. Bisa jelaskan lebih detail?";
        }
        
        $message = count($transactions) > 1 
            ? "Saya mendeteksi " . count($transactions) . " transaksi:\n\n" 
            : "Saya mendeteksi transaksi berikut:\n\n";
        
        foreach ($transactions as $i => $tx) {
            $typeLabel = match($tx['type']) {
                'income' => 'Pemasukan',
                'expense' => 'Pengeluaran',
                'transfer' => 'Transfer',
                default => ucfirst($tx['type'])
            };
            
            $timeInfo = $tx['time_hint'] ? " ({$tx['time_hint']})" : '';
            
            $message .= sprintf(
                "%d. %s: %s\n   Kategori: %s\n   Akun: %s\n   Tanggal: %s%s\n\n",
                $i + 1,
                $typeLabel,
                AmountParser::format($tx['amount'], $tx['currency']),
                $tx['category_name'],
                $tx['account_name'],
                Carbon::parse($tx['date'])->format('d M Y'),
                $timeInfo
            );
        }
        
        if (!empty($ambiguities)) {
            $message .= "⚠️ Catatan:\n";
            foreach ($ambiguities as $ambiguity) {
                $message .= "- " . $ambiguity['message'] . "\n";
            }
            $message .= "\n";
        }
        
        $message .= "Mau dicatat semuanya?";
        
        return $message;
    }

    /**
     * Prepare transactions for batch saving (after user confirmation).
     */
    public static function prepareForSave(array $detectedTransactions): array
    {
        return array_map(function ($tx) {
            return [
                'type' => $tx['type'],
                'amount' => $tx['amount'],
                'description' => $tx['description'],
                'category_id' => $tx['category_id'],
                'account_id' => $tx['account_id'],
                'transaction_date' => $tx['date'],
                'transaction_time' => $tx['date'] . ' ' . $tx['time'],
            ];
        }, $detectedTransactions);
    }
}
