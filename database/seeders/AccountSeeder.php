<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Account;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        
        foreach ($users as $user) {
            echo "Creating accounts for user: {$user->name}\n";
            
            // Create default accounts for each user
            $this->createDefaultAccounts($user);
        }
        
        echo "Account seeding completed!\n";
    }
    
    private function createDefaultAccounts(User $user): void
    {
        $defaultAccounts = [
            [
                'name' => 'Cash',
                'type' => 'cash',
                'balance' => rand(500000, 2000000),
                'color' => 'green',
                'icon' => 'banknotes',
                'description' => 'Cash on hand',
                'is_default' => true
            ],
            [
                'name' => 'Bank BCA',
                'type' => 'bank',
                'balance' => rand(5000000, 25000000),
                'color' => 'blue',
                'icon' => 'building-library',
                'description' => 'Bank BCA Savings Account'
            ],
            [
                'name' => 'Bank Mandiri',
                'type' => 'bank',
                'balance' => rand(3000000, 15000000),
                'color' => 'blue',
                'icon' => 'building-library',
                'description' => 'Bank Mandiri Savings Account'
            ],
            [
                'name' => 'Credit Card BCA',
                'type' => 'credit_card',
                'balance' => rand(-2000000, -500000),
                'color' => 'purple',
                'icon' => 'credit-card',
                'description' => 'BCA Credit Card'
            ],
            [
                'name' => 'GoPay',
                'type' => 'wallet',
                'balance' => rand(100000, 500000),
                'color' => 'orange',
                'icon' => 'wallet',
                'description' => 'GoPay Digital Wallet'
            ],
            [
                'name' => 'OVO',
                'type' => 'wallet',
                'balance' => rand(50000, 300000),
                'color' => 'orange',
                'icon' => 'wallet',
                'description' => 'OVO Digital Wallet'
            ],
            [
                'name' => 'Investment Portfolio',
                'type' => 'investment',
                'balance' => rand(10000000, 50000000),
                'color' => 'yellow',
                'icon' => 'chart-bar',
                'description' => 'Stock and mutual fund investments'
            ]
        ];
        
        foreach ($defaultAccounts as $accountData) {
            Account::create([
                'user_id' => $user->id,
                ...$accountData
            ]);
            
            echo "Created account: {$accountData['name']} with balance Rp " . number_format($accountData['balance']) . "\n";
        }
    }
}
