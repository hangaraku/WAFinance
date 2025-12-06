<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\Budget;
use Carbon\Carbon;

class September2025Seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $daysInMonth = 30; // September has 30 days
        
        foreach ($users as $user) {
            echo "Seeding September 2025 data for user: {$user->name}\n";
            
            // Get user categories
            $categories = Category::where('user_id', $user->id)->get();
            
            // Create income transactions for September
            $this->createSeptemberIncome($user, $categories, $daysInMonth);
            
            // Create expense transactions for September
            $this->createSeptemberExpenses($user, $categories, $daysInMonth);
            
            // Create budgets for September
            $this->createSeptemberBudgets($user, $categories);
        }
        
        echo "September 2025 data seeding completed!\n";
    }
    
    private function createSeptemberIncome(User $user, $categories, $daysInMonth)
    {
        // Salary (usually around 25th)
        $salaryCategory = $categories->where('name', 'Gaji')->first();
        if ($salaryCategory) {
            $salaryDay = 25;
            $baseSalary = rand(4000000, 8000000);
            $allowances = rand(500000, 1500000);
            $totalSalary = $baseSalary + $allowances;
            
            Transaction::create([
                'user_id' => $user->id,
                'category_id' => $salaryCategory->id,
                'type' => 'income',
                'amount' => $totalSalary,
                'description' => 'Gaji Pokok + Tunjangan September',
                'notes' => 'Transfer dari perusahaan',
                'transaction_date' => "2025-09-{$salaryDay}",
                'transaction_time' => '09:00:00'
            ]);
            
            echo "Created salary transaction: Rp " . number_format($totalSalary) . "\n";
        }
        
        // Bonus (end of month)
        $bonusCategory = $categories->where('name', 'Bonus')->first();
        if ($bonusCategory) {
            $bonusDay = 30;
            $bonusAmount = rand(1000000, 3000000);
            
            Transaction::create([
                'user_id' => $user->id,
                'category_id' => $bonusCategory->id,
                'type' => 'income',
                'amount' => $bonusAmount,
                'description' => 'Bonus Performa September',
                'notes' => 'Bonus bulanan',
                'transaction_date' => "2025-09-{$bonusDay}",
                'transaction_time' => '10:00:00'
            ]);
            
            echo "Created bonus transaction: Rp " . number_format($bonusAmount) . "\n";
        }
        
        // Investment returns (mid month)
        $investmentCategory = $categories->where('name', 'Investasi')->first();
        if ($investmentCategory) {
            $investDay = 15;
            $returnAmount = rand(200000, 800000);
            
            Transaction::create([
                'user_id' => $user->id,
                'category_id' => $investmentCategory->id,
                'type' => 'income',
                'amount' => $returnAmount,
                'description' => 'Return Investasi Saham',
                'notes' => 'Dividen bulanan',
                'transaction_date' => "2025-09-{$investDay}",
                'transaction_time' => '14:00:00'
            ]);
            
            echo "Created investment transaction: Rp " . number_format($returnAmount) . "\n";
        }
        
        // Side hustle (random throughout month)
        $sideHustleCategory = $categories->where('name', 'Side Hustle')->first();
        if ($sideHustleCategory) {
            $projectDay = rand(10, 20);
            $projectAmount = rand(500000, 2000000);
            
            Transaction::create([
                'user_id' => $user->id,
                'category_id' => $sideHustleCategory->id,
                'type' => 'income',
                'amount' => $projectAmount,
                'description' => 'Project Freelance Web',
                'notes' => 'Client project selesai',
                'transaction_date' => "2025-09-{$projectDay}",
                'transaction_time' => '16:00:00'
            ]);
            
            echo "Created side hustle transaction: Rp " . number_format($projectAmount) . "\n";
        }
    }
    
    private function createSeptemberExpenses(User $user, $categories, $daysInMonth)
    {
        // Create 15-20 random expense transactions
        $numExpenses = rand(15, 20);
        
        for ($i = 0; $i < $numExpenses; $i++) {
            $category = $categories->where('type', 'expense')->random();
            $day = rand(1, $daysInMonth);
            $amount = rand(50000, 500000);
            
            // Generate realistic descriptions based on category
            $description = $this->generateExpenseDescription($category->name);
            
            Transaction::create([
                'user_id' => $user->id,
                'category_id' => $category->id,
                'type' => 'expense',
                'amount' => $amount,
                'description' => $description,
                'notes' => 'Transaksi September',
                'transaction_date' => "2025-09-{$day}",
                'transaction_time' => $this->generateRandomTime()
            ]);
        }
        
        echo "Created {$numExpenses} expense transactions\n";
    }
    
    private function createSeptemberBudgets(User $user, $categories)
    {
        $expenseCategories = $categories->where('type', 'expense');
        
        foreach ($expenseCategories as $category) {
            $budgetAmount = rand(500000, 2000000);
            
            Budget::firstOrCreate([
                'user_id' => $user->id,
                'category_id' => $category->id,
                'month' => 9,
                'year' => 2025
            ], [
                'amount' => $budgetAmount
            ]);
        }
        
        echo "Created budgets for September 2025\n";
    }
    
    private function generateExpenseDescription(string $categoryName): string
    {
        return match ($categoryName) {
            'Makan' => $this->getRandomFoodDescription(),
            'Transport' => $this->getRandomTransportDescription(),
            'Belanja' => $this->getRandomShoppingDescription(),
            'Tagihan' => $this->getRandomBillDescription(),
            'Hiburan' => $this->getRandomEntertainmentDescription(),
            'Kesehatan' => $this->getRandomHealthDescription(),
            'Pendidikan' => $this->getRandomEducationDescription(),
            default => 'Pengeluaran ' . $categoryName
        };
    }
    
    private function getRandomFoodDescription(): string
    {
        $descriptions = [
            'Makan Siang Kantor',
            'Sarapan Pagi',
            'Makan Malam',
            'Snack Sore',
            'Kopi Pagi',
            'Lunch Meeting',
            'Dinner Keluarga',
            'Jajan Siang'
        ];
        
        return $descriptions[array_rand($descriptions)];
    }
    
    private function getRandomTransportDescription(): string
    {
        $descriptions = [
            'Bensin Motor',
            'Ojek Online',
            'Parkir Mall',
            'Tol Jalan',
            'Bensin Mobil',
            'Parkir Kantor',
            'GoPay Transport',
            'Parkir Rumah'
        ];
        
        return $descriptions[array_rand($descriptions)];
    }
    
    private function getRandomShoppingDescription(): string
    {
        $descriptions = [
            'Belanja Bulanan',
            'Baju Kerja',
            'Alat Rumah Tangga',
            'Kebutuhan Dapur',
            'Peralatan Mandi',
            'Buku Bacaan',
            'Mainan Anak',
            'Aksesoris'
        ];
        
        return $descriptions[array_rand($descriptions)];
    }
    
    private function getRandomBillDescription(): string
    {
        $descriptions = [
            'Tagihan Listrik',
            'Tagihan Air',
            'Tagihan Internet',
            'Tagihan Telepon',
            'Tagihan Asuransi',
            'Tagihan Kartu Kredit',
            'Tagihan TV Kabel',
            'Tagihan Maintenance'
        ];
        
        return $descriptions[array_rand($descriptions)];
    }
    
    private function getRandomEntertainmentDescription(): string
    {
        $descriptions = [
            'Nonton Bioskop',
            'Game Online',
            'Karaoke',
            'Hobi Photography',
            'Gym Membership',
            'Streaming Service',
            'Game Console',
            'Hobi Collection'
        ];
        
        return $descriptions[array_rand($descriptions)];
    }
    
    private function getRandomHealthDescription(): string
    {
        $descriptions = [
            'Kontrol Dokter',
            'Obat-obatan',
            'Vitamin',
            'Medical Checkup',
            'Dental Care',
            'Eye Care',
            'Fitness Supplements',
            'Health Insurance'
        ];
        
        return $descriptions[array_rand($descriptions)];
    }
    
    private function getRandomEducationDescription(): string
    {
        $descriptions = [
            'Kursus Online',
            'Buku Pelajaran',
            'Workshop',
            'Seminar',
            'Training',
            'E-book',
            'Online Course',
            'Study Materials'
        ];
        
        return $descriptions[array_rand($descriptions)];
    }
    
    private function generateRandomTime(): string
    {
        $hours = str_pad(rand(6, 22), 2, '0', STR_PAD_LEFT);
        $minutes = str_pad(rand(0, 59), 2, '0', STR_PAD_LEFT);
        $seconds = str_pad(rand(0, 59), 2, '0', STR_PAD_LEFT);
        
        return "{$hours}:{$minutes}:{$seconds}";
    }
}
