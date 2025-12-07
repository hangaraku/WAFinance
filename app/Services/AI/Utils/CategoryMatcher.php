<?php

namespace App\Services\AI\Utils;

use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class CategoryMatcher
{
    /**
     * Keyword to category mappings for Indonesian/English
     * Format: 'keyword' => ['category_names' => [...], 'type' => 'expense|income']
     */
    protected static array $keywordMappings = [
        // Food & Drinks
        'makan' => ['names' => ['Makanan', 'Food', 'Makan'], 'type' => 'expense'],
        'makanan' => ['names' => ['Makanan', 'Food', 'Makan'], 'type' => 'expense'],
        'food' => ['names' => ['Makanan', 'Food', 'Makan'], 'type' => 'expense'],
        'lunch' => ['names' => ['Makanan', 'Food', 'Makan'], 'type' => 'expense'],
        'dinner' => ['names' => ['Makanan', 'Food', 'Makan'], 'type' => 'expense'],
        'breakfast' => ['names' => ['Makanan', 'Food', 'Makan'], 'type' => 'expense'],
        'sarapan' => ['names' => ['Makanan', 'Food', 'Makan'], 'type' => 'expense'],
        'makan siang' => ['names' => ['Makanan', 'Food', 'Makan'], 'type' => 'expense'],
        'makan malam' => ['names' => ['Makanan', 'Food', 'Makan'], 'type' => 'expense'],
        'nasi' => ['names' => ['Makanan', 'Food', 'Makan'], 'type' => 'expense'],
        'snack' => ['names' => ['Makanan', 'Food', 'Snack', 'Jajanan'], 'type' => 'expense'],
        'jajan' => ['names' => ['Makanan', 'Food', 'Snack', 'Jajanan'], 'type' => 'expense'],
        'cemilan' => ['names' => ['Makanan', 'Food', 'Snack', 'Jajanan'], 'type' => 'expense'],
        
        // Drinks
        'minum' => ['names' => ['Minuman', 'Drinks', 'Minum'], 'type' => 'expense'],
        'minuman' => ['names' => ['Minuman', 'Drinks', 'Minum'], 'type' => 'expense'],
        'drinks' => ['names' => ['Minuman', 'Drinks', 'Minum'], 'type' => 'expense'],
        'kopi' => ['names' => ['Minuman', 'Drinks', 'Minum', 'Coffee'], 'type' => 'expense'],
        'coffee' => ['names' => ['Minuman', 'Drinks', 'Minum', 'Coffee'], 'type' => 'expense'],
        'teh' => ['names' => ['Minuman', 'Drinks', 'Minum'], 'type' => 'expense'],
        'tea' => ['names' => ['Minuman', 'Drinks', 'Minum'], 'type' => 'expense'],
        'boba' => ['names' => ['Minuman', 'Drinks', 'Minum'], 'type' => 'expense'],
        'jus' => ['names' => ['Minuman', 'Drinks', 'Minum'], 'type' => 'expense'],
        'juice' => ['names' => ['Minuman', 'Drinks', 'Minum'], 'type' => 'expense'],
        
        // Transportation
        'transport' => ['names' => ['Transport', 'Transportasi', 'Transportation'], 'type' => 'expense'],
        'transportasi' => ['names' => ['Transport', 'Transportasi', 'Transportation'], 'type' => 'expense'],
        'ojek' => ['names' => ['Transport', 'Transportasi', 'Ojek'], 'type' => 'expense'],
        'gojek' => ['names' => ['Transport', 'Transportasi', 'Ojek'], 'type' => 'expense'],
        'grab' => ['names' => ['Transport', 'Transportasi', 'Ojek'], 'type' => 'expense'],
        'taxi' => ['names' => ['Transport', 'Transportasi', 'Taxi'], 'type' => 'expense'],
        'taksi' => ['names' => ['Transport', 'Transportasi', 'Taxi'], 'type' => 'expense'],
        'bus' => ['names' => ['Transport', 'Transportasi'], 'type' => 'expense'],
        'kereta' => ['names' => ['Transport', 'Transportasi'], 'type' => 'expense'],
        'train' => ['names' => ['Transport', 'Transportasi'], 'type' => 'expense'],
        'mrt' => ['names' => ['Transport', 'Transportasi'], 'type' => 'expense'],
        'bensin' => ['names' => ['Transport', 'Transportasi', 'Fuel', 'Bensin'], 'type' => 'expense'],
        'fuel' => ['names' => ['Transport', 'Transportasi', 'Fuel', 'Bensin'], 'type' => 'expense'],
        'parkir' => ['names' => ['Transport', 'Transportasi', 'Parking'], 'type' => 'expense'],
        'parking' => ['names' => ['Transport', 'Transportasi', 'Parking'], 'type' => 'expense'],
        
        // Shopping
        'belanja' => ['names' => ['Shopping', 'Belanja'], 'type' => 'expense'],
        'shopping' => ['names' => ['Shopping', 'Belanja'], 'type' => 'expense'],
        'beli' => ['names' => ['Shopping', 'Belanja'], 'type' => 'expense'],
        'baju' => ['names' => ['Shopping', 'Belanja', 'Clothing'], 'type' => 'expense'],
        'pakaian' => ['names' => ['Shopping', 'Belanja', 'Clothing'], 'type' => 'expense'],
        'sepatu' => ['names' => ['Shopping', 'Belanja', 'Clothing'], 'type' => 'expense'],
        
        // Entertainment
        'hiburan' => ['names' => ['Entertainment', 'Hiburan'], 'type' => 'expense'],
        'entertainment' => ['names' => ['Entertainment', 'Hiburan'], 'type' => 'expense'],
        'nonton' => ['names' => ['Entertainment', 'Hiburan', 'Movie'], 'type' => 'expense'],
        'movie' => ['names' => ['Entertainment', 'Hiburan', 'Movie'], 'type' => 'expense'],
        'film' => ['names' => ['Entertainment', 'Hiburan', 'Movie'], 'type' => 'expense'],
        'bioskop' => ['names' => ['Entertainment', 'Hiburan', 'Movie'], 'type' => 'expense'],
        'game' => ['names' => ['Entertainment', 'Hiburan', 'Gaming'], 'type' => 'expense'],
        'gaming' => ['names' => ['Entertainment', 'Hiburan', 'Gaming'], 'type' => 'expense'],
        
        // Bills & Utilities
        'listrik' => ['names' => ['Bills', 'Tagihan', 'Utilities'], 'type' => 'expense'],
        'electricity' => ['names' => ['Bills', 'Tagihan', 'Utilities'], 'type' => 'expense'],
        'air' => ['names' => ['Bills', 'Tagihan', 'Utilities'], 'type' => 'expense'],
        'water' => ['names' => ['Bills', 'Tagihan', 'Utilities'], 'type' => 'expense'],
        'internet' => ['names' => ['Bills', 'Tagihan', 'Utilities', 'Internet'], 'type' => 'expense'],
        'wifi' => ['names' => ['Bills', 'Tagihan', 'Utilities', 'Internet'], 'type' => 'expense'],
        'pulsa' => ['names' => ['Bills', 'Tagihan', 'Phone'], 'type' => 'expense'],
        'kuota' => ['names' => ['Bills', 'Tagihan', 'Phone', 'Internet'], 'type' => 'expense'],
        'tagihan' => ['names' => ['Bills', 'Tagihan'], 'type' => 'expense'],
        'bills' => ['names' => ['Bills', 'Tagihan'], 'type' => 'expense'],
        
        // Health
        'kesehatan' => ['names' => ['Health', 'Kesehatan'], 'type' => 'expense'],
        'health' => ['names' => ['Health', 'Kesehatan'], 'type' => 'expense'],
        'obat' => ['names' => ['Health', 'Kesehatan', 'Medicine'], 'type' => 'expense'],
        'medicine' => ['names' => ['Health', 'Kesehatan', 'Medicine'], 'type' => 'expense'],
        'dokter' => ['names' => ['Health', 'Kesehatan', 'Doctor'], 'type' => 'expense'],
        'doctor' => ['names' => ['Health', 'Kesehatan', 'Doctor'], 'type' => 'expense'],
        'rumah sakit' => ['names' => ['Health', 'Kesehatan'], 'type' => 'expense'],
        'hospital' => ['names' => ['Health', 'Kesehatan'], 'type' => 'expense'],
        
        // Education
        'pendidikan' => ['names' => ['Education', 'Pendidikan'], 'type' => 'expense'],
        'education' => ['names' => ['Education', 'Pendidikan'], 'type' => 'expense'],
        'sekolah' => ['names' => ['Education', 'Pendidikan'], 'type' => 'expense'],
        'kuliah' => ['names' => ['Education', 'Pendidikan'], 'type' => 'expense'],
        'kursus' => ['names' => ['Education', 'Pendidikan'], 'type' => 'expense'],
        'buku' => ['names' => ['Education', 'Pendidikan', 'Books'], 'type' => 'expense'],
        'book' => ['names' => ['Education', 'Pendidikan', 'Books'], 'type' => 'expense'],
        
        // Income categories
        'gaji' => ['names' => ['Salary', 'Gaji'], 'type' => 'income'],
        'salary' => ['names' => ['Salary', 'Gaji'], 'type' => 'income'],
        'bonus' => ['names' => ['Bonus', 'Bonus'], 'type' => 'income'],
        'freelance' => ['names' => ['Freelance', 'Side Income'], 'type' => 'income'],
        'sampingan' => ['names' => ['Freelance', 'Side Income', 'Sampingan'], 'type' => 'income'],
        'investasi' => ['names' => ['Investment', 'Investasi'], 'type' => 'income'],
        'investment' => ['names' => ['Investment', 'Investasi'], 'type' => 'income'],
        'dividen' => ['names' => ['Investment', 'Investasi', 'Dividend'], 'type' => 'income'],
        'dividend' => ['names' => ['Investment', 'Investasi', 'Dividend'], 'type' => 'income'],
        'hadiah' => ['names' => ['Gift', 'Hadiah'], 'type' => 'income'],
        'gift' => ['names' => ['Gift', 'Hadiah'], 'type' => 'income'],
        'terima' => ['names' => ['Other Income', 'Lainnya'], 'type' => 'income'],
        'dapat' => ['names' => ['Other Income', 'Lainnya'], 'type' => 'income'],
    ];

    /**
     * Match a keyword or phrase to user's categories.
     *
     * @param User $user The user whose categories to search
     * @param string $text The text to analyze for category keywords
     * @param string|null $typeHint Optional type hint ('income' or 'expense')
     * @return array|null Matched category or null if no match
     */
    public static function match(User $user, string $text, ?string $typeHint = null): ?array
    {
        $text = strtolower(trim($text));
        $userCategories = self::getUserCategories($user);

        // First, try exact keyword matching
        foreach (self::$keywordMappings as $keyword => $mapping) {
            if (str_contains($text, $keyword)) {
                // If type hint is provided, verify it matches
                if ($typeHint && $mapping['type'] !== $typeHint) {
                    continue;
                }

                // Find matching user category
                foreach ($mapping['names'] as $categoryName) {
                    foreach ($userCategories as $category) {
                        if (strtolower($category['name']) === strtolower($categoryName)) {
                            // Verify type matches if hint provided
                            if ($typeHint && $category['type'] !== $typeHint) {
                                continue;
                            }
                            return $category;
                        }
                    }
                }

                // If no exact match, return first category of matching type
                $type = $typeHint ?? $mapping['type'];
                foreach ($userCategories as $category) {
                    if ($category['type'] === $type) {
                        return $category;
                    }
                }
            }
        }

        // Second, try fuzzy matching against user's category names
        foreach ($userCategories as $category) {
            $categoryName = strtolower($category['name']);
            if (str_contains($text, $categoryName) || str_contains($categoryName, $text)) {
                if ($typeHint && $category['type'] !== $typeHint) {
                    continue;
                }
                return $category;
            }
        }

        // Return default category for type if available
        if ($typeHint) {
            foreach ($userCategories as $category) {
                if ($category['type'] === $typeHint && ($category['is_default'] ?? false)) {
                    return $category;
                }
            }
            // Just return first of type
            foreach ($userCategories as $category) {
                if ($category['type'] === $typeHint) {
                    return $category;
                }
            }
        }

        return null;
    }

    /**
     * Detect transaction type from text.
     *
     * @param string $text The text to analyze
     * @return string 'expense' or 'income' or 'transfer'
     */
    public static function detectType(string $text): string
    {
        $text = strtolower($text);

        // Transfer indicators
        $transferKeywords = ['transfer', 'kirim', 'pindah', 'move', 'send'];
        foreach ($transferKeywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return 'transfer';
            }
        }

        // Income indicators
        $incomeKeywords = [
            'gaji', 'salary', 'terima', 'dapat', 'receive', 'income', 
            'pemasukan', 'bonus', 'hadiah', 'gift', 'refund', 'cashback'
        ];
        foreach ($incomeKeywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return 'income';
            }
        }

        // Expense indicators (also default)
        $expenseKeywords = [
            'beli', 'bayar', 'pay', 'buat', 'spent', 'spend', 'keluar',
            'expense', 'pengeluaran', 'makan', 'minum', 'ongkos', 'biaya'
        ];
        foreach ($expenseKeywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return 'expense';
            }
        }

        // Default to expense for ambiguous cases
        return 'expense';
    }

    /**
     * Get user's categories with caching.
     *
     * @param User $user
     * @return array
     */
    protected static function getUserCategories(User $user): array
    {
        $cacheKey = "user_categories_{$user->id}";
        
        return Cache::remember($cacheKey, 300, function () use ($user) {
            return Category::where('user_id', $user->id)
                ->get()
                ->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'type' => $category->type,
                        'is_default' => $category->is_default ?? false,
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Extract keywords that could indicate categories from text.
     *
     * @param string $text
     * @return array List of detected keywords
     */
    public static function extractKeywords(string $text): array
    {
        $text = strtolower($text);
        $foundKeywords = [];

        foreach (self::$keywordMappings as $keyword => $mapping) {
            if (str_contains($text, $keyword)) {
                $foundKeywords[] = [
                    'keyword' => $keyword,
                    'type' => $mapping['type'],
                    'suggested_categories' => $mapping['names']
                ];
            }
        }

        return $foundKeywords;
    }

    /**
     * Invalidate user's category cache (call when categories are updated).
     *
     * @param User $user
     */
    public static function invalidateCache(User $user): void
    {
        Cache::forget("user_categories_{$user->id}");
    }
}
