<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\User;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultCategories = [
            // Expense categories
            ['name' => 'Makan', 'icon' => 'cake', 'color' => '#FF6B35', 'type' => 'expense'],
            ['name' => 'Transportasi', 'icon' => 'truck', 'color' => '#4F46E5', 'type' => 'expense'],
            ['name' => 'Tagihan', 'icon' => 'document-text', 'color' => '#DC2626', 'type' => 'expense'],
            ['name' => 'Belanja', 'icon' => 'shopping-bag', 'color' => '#059669', 'type' => 'expense'],
            ['name' => 'Lain-lain', 'icon' => 'dots-horizontal', 'color' => '#6B7280', 'type' => 'expense'],
            
            // Income categories
            ['name' => 'Gaji', 'icon' => 'currency-dollar', 'color' => '#10B981', 'type' => 'income'],
            ['name' => 'Bonus', 'icon' => 'gift', 'color' => '#F59E0B', 'type' => 'income'],
            ['name' => 'Investasi', 'icon' => 'trending-up', 'color' => '#8B5CF6', 'type' => 'income'],
            ['name' => 'Lain-lain', 'icon' => 'plus-circle', 'color' => '#6B7280', 'type' => 'income'],
        ];

        // Get all users and create default categories for each
        User::all()->each(function ($user) use ($defaultCategories) {
            foreach ($defaultCategories as $category) {
                // Check if category already exists for this user
                $exists = Category::where('user_id', $user->id)
                    ->where('name', $category['name'])
                    ->exists();
                
                if (!$exists) {
                    Category::create([
                        'user_id' => $user->id,
                        'name' => $category['name'],
                        'icon' => $category['icon'],
                        'color' => $category['color'],
                        'type' => $category['type'],
                        'is_default' => true,
                    ]);
                }
            }
        });
    }
}
