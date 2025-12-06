<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Budget;
use App\Models\Category;
use App\Models\User;
use Carbon\Carbon;

class BudgetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        
        foreach ($users as $user) {
            $this->createBudgetsForUser($user);
        }
    }

    private function createBudgetsForUser(User $user)
    {
        $categories = Category::where('user_id', $user->id)->get();
        
        // Get current month and year
        $currentDate = Carbon::now();
        $currentMonth = $currentDate->month;
        $currentYear = $currentDate->year;
        
        // Create budgets for the last 3 months and current month
        for ($month = $currentMonth - 2; $month <= $currentMonth; $month++) {
            if ($month <= 0) {
                $month += 12;
                $year = $currentYear - 1;
            } else {
                $year = $currentYear;
            }
            
            $this->createMonthlyBudgets($user, $categories, $month, $year);
        }
    }

    private function createMonthlyBudgets(User $user, $categories, $month, $year)
    {
        $expenseCategories = $categories->where('type', 'expense');
        
        foreach ($expenseCategories as $category) {
            $budgetAmount = $this->getBudgetAmount($category->name);
            
            Budget::create([
                'user_id' => $user->id,
                'category_id' => $category->id,
                'amount' => $budgetAmount,
                'month' => $month,
                'year' => $year,
            ]);
        }
    }

    private function getBudgetAmount(string $categoryName): int
    {
        return match ($categoryName) {
            'Makan' => rand(800000, 1500000), // 800k - 1.5M per bulan
            'Transportasi' => rand(300000, 800000), // 300k - 800k per bulan
            'Tagihan' => rand(500000, 3000000), // 500k - 3M per bulan
            'Belanja' => rand(500000, 2000000), // 500k - 2M per bulan
            'Lain-lain' => rand(200000, 1000000), // 200k - 1M per bulan
            default => rand(300000, 1000000),
        };
    }
}
