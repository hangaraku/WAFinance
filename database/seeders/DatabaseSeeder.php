<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create users first
        $this->call([
            UserSeeder::class,
            AccountSeeder::class, // Create accounts before transactions
            CategorySeeder::class,
            IncomeSeeder::class,
            TransactionSeeder::class,
            BudgetSeeder::class,
            GoalSeeder::class,
        ]);
    }
}
