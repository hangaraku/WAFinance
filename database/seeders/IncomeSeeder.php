<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Transaction;
use App\Models\Category;
use App\Models\User;
use Carbon\Carbon;

class IncomeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        
        foreach ($users as $user) {
            $this->createIncomeForUser($user);
        }
    }

    private function createIncomeForUser(User $user)
    {
        $categories = Category::where('user_id', $user->id)->where('type', 'income')->get();
        
        // Get current month and year
        $currentDate = Carbon::now();
        $currentMonth = $currentDate->month;
        $currentYear = $currentDate->year;
        
        // Create income for the last 6 months
        for ($month = $currentMonth - 5; $month <= $currentMonth; $month++) {
            if ($month <= 0) {
                $month += 12;
                $year = $currentYear - 1;
            } else {
                $year = $currentYear;
            }
            
            $this->createMonthlyIncome($user, $categories, $month, $year);
        }
    }

    private function createMonthlyIncome(User $user, $categories, $month, $year)
    {
        $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;
        $currentDate = Carbon::now();
        // If seeding the current month, only allow days up to today
        $effectiveDays = ($year == $currentDate->year && $month == $currentDate->month) ? $currentDate->day : $daysInMonth;

        // 1. Gaji Pokok (Salary) - Usually on 25th or 28th
        $this->createSalaryIncome($user, $categories, $month, $year, $effectiveDays);
        
        // 2. Bonus dan Tunjangan (Bonuses & Allowances)
        $this->createBonusIncome($user, $categories, $month, $year, $effectiveDays);
        
        // 3. Investasi dan Dividen (Investment Returns)
        $this->createInvestmentIncome($user, $categories, $month, $year, $effectiveDays);
        
        // 4. Side Hustle dan Freelance
        $this->createSideHustleIncome($user, $categories, $month, $year, $effectiveDays);
        
        // 5. Refund dan Kompensasi
        $this->createRefundIncome($user, $categories, $month, $year, $effectiveDays);
        
        // 6. Hadiah dan Hibah
        $this->createGiftIncome($user, $categories, $month, $year, $effectiveDays);
    }

    private function createSalaryIncome(User $user, $categories, $month, $year, $daysInMonth)
    {
        $salaryCategory = $categories->where('name', 'Gaji')->first();
        if (!$salaryCategory) return;
        
        // Salary usually on 25th or 28th, December earlier
        $salaryDay = $month == 12 ? 20 : (rand(1, 2) == 1 ? 25 : 28);
        if ($salaryDay <= $daysInMonth) {
            $baseSalary = rand(4000000, 8000000); // 4M - 8M base salary
            $allowances = rand(500000, 1500000); // 500k - 1.5M allowances
            $totalSalary = $baseSalary + $allowances;
            
            $this->createTransaction($user, $salaryCategory, 'income', $totalSalary, 'Gaji Pokok + Tunjangan', $year, $month, $salaryDay, '09:00');
        }
    }

    private function createBonusIncome(User $user, $categories, $month, $year, $daysInMonth)
    {
        $bonusCategory = $categories->where('name', 'Bonus')->first();
        if (!$bonusCategory) return;
        
        // Performance bonus (usually end of month)
        if (rand(1, 3) == 1) { // 33% chance
            $bonusDay = $this->pickDay($daysInMonth - 5, $daysInMonth);
            $bonusAmount = rand(500000, 3000000);
            $bonusTypes = [
                'Bonus Performa Bulanan',
                'Bonus Target Sales',
                'Bonus Project Selesai',
                'Bonus Kerja Lembur',
                'Bonus Kinerja Tim'
            ];
            
            $this->createTransaction($user, $bonusCategory, 'income', $bonusAmount, $bonusTypes[array_rand($bonusTypes)], $year, $month, $bonusDay, '10:00');
        }
        
        // Annual bonus (usually in December or March)
        if (($month == 12 || $month == 3) && rand(1, 2) == 1) {
            $bonusDay = $this->pickDay(15, $daysInMonth);
            $annualBonus = rand(2000000, 8000000);
            
            $this->createTransaction($user, $bonusCategory, 'income', $annualBonus, 'Bonus Tahunan', $year, $month, $bonusDay, '11:00');
        }
    }

    private function createInvestmentIncome(User $user, $categories, $month, $year, $daysInMonth)
    {
        $investmentCategory = $categories->where('name', 'Investasi')->first();
        if (!$investmentCategory) return;
        
        // Monthly investment returns (random days)
        if (rand(1, 3) == 1) { // 33% chance
            $investDay = $this->pickDay(10, $daysInMonth);
            $returnAmount = rand(100000, 800000);
            $returnTypes = [
                'Return Investasi Saham',
                'Dividen Saham Blue Chip',
                'Profit Trading Forex',
                'Return Reksadana',
                'Bunga Deposito',
                'Return Crypto',
                'Profit Jual Saham',
                'Dividen Saham Dividen'
            ];
            
            $this->createTransaction($user, $investmentCategory, 'income', $returnAmount, $returnTypes[array_rand($returnTypes)], $year, $month, $investDay, '14:00');
        }
        
        // Quarterly dividends (March, June, September, December)
        if (in_array($month, [3, 6, 9, 12]) && rand(1, 2) == 1) {
            $dividendDay = $this->pickDay(20, $daysInMonth);
            $dividendAmount = rand(300000, 1200000);
            
            $this->createTransaction($user, $investmentCategory, 'income', $dividendAmount, 'Dividen Kuartalan', $year, $month, $dividendDay, '15:00');
        }
    }

    private function createSideHustleIncome(User $user, $categories, $month, $year, $daysInMonth)
    {
        // Create a new category if it doesn't exist
        $sideHustleCategory = $categories->where('name', 'Side Hustle')->first();
        if (!$sideHustleCategory) {
            $sideHustleCategory = Category::firstOrCreate([
                'user_id' => $user->id,
                'name' => 'Side Hustle'
            ], [
                'icon' => 'briefcase',
                'color' => 'purple',
                'type' => 'income',
                'is_default' => false
            ]);
        }
        
        // Freelance projects (random throughout month)
        if (rand(1, 4) == 1) { // 25% chance
            $projectDay = $this->pickDay(5, $daysInMonth);
            $projectAmount = rand(500000, 3000000);
            $projectTypes = [
                'Project Freelance Web',
                'Design Logo Client',
                'Jasa Konsultasi IT',
                'Project Mobile App',
                'Jasa Writing Artikel',
                'Project Video Editing',
                'Jasa Photography',
                'Project Social Media'
            ];
            
            $this->createTransaction($user, $sideHustleCategory, 'income', $projectAmount, $projectTypes[array_rand($projectTypes)], $year, $month, $projectDay, '16:00');
        }
        
        // Online business (consistent monthly)
        if (rand(1, 2) == 1) { // 50% chance
            $businessDay = $this->pickDay(15, $daysInMonth);
            $businessAmount = rand(200000, 1000000);
            $businessTypes = [
                'Penjualan Online Shop',
                'Komisi Affiliate',
                'Pendapatan YouTube',
                'Pendapatan Blog',
                'Penjualan Digital Product',
                'Komisi MLM',
                'Pendapatan Podcast',
                'Penjualan Course Online'
            ];
            
            $this->createTransaction($user, $sideHustleCategory, 'income', $businessAmount, $businessTypes[array_rand($businessTypes)], $year, $month, $businessDay, '17:00');
        }
    }

    private function createRefundIncome(User $user, $categories, $month, $year, $daysInMonth)
    {
        // Create refund category if it doesn't exist
        $refundCategory = $categories->where('name', 'Refund')->first();
        if (!$refundCategory) {
            $refundCategory = Category::firstOrCreate([
                'user_id' => $user->id,
                'name' => 'Refund'
            ], [
                'icon' => 'arrow-uturn-left',
                'color' => 'green',
                'type' => 'income',
                'is_default' => false
            ]);
        }
        
        // Tax refunds (usually in March-April)
        if (in_array($month, [3, 4]) && rand(1, 3) == 1) {
            $refundDay = $this->pickDay(10, $daysInMonth);
            $taxRefund = rand(1000000, 5000000);
            
            $this->createTransaction($user, $refundCategory, 'income', $taxRefund, 'Pajak Refund', $year, $month, $refundDay, '12:00');
        }
        
        // Other refunds (random)
        if (rand(1, 5) == 1) { // 20% chance
            $refundDay = $this->pickDay(1, $daysInMonth);
            $refundAmount = rand(50000, 500000);
            $refundTypes = [
                'Refund Belanja Online',
                'Refund Tiket Pesawat',
                'Refund Hotel',
                'Refund Asuransi',
                'Refund Kartu Kredit',
                'Refund Membership',
                'Refund Subscription',
                'Refund Jasa'
            ];
            
            $this->createTransaction($user, $refundCategory, 'income', $refundAmount, $refundTypes[array_rand($refundTypes)], $year, $month, $refundDay, '13:00');
        }
    }

    private function createGiftIncome(User $user, $categories, $month, $year, $daysInMonth)
    {
        // Create gift category if it doesn't exist
        $giftCategory = $categories->where('name', 'Hadiah')->first();
        if (!$giftCategory) {
            $giftCategory = Category::firstOrCreate([
                'user_id' => $user->id,
                'name' => 'Hadiah'
            ], [
                'icon' => 'gift',
                'color' => 'pink',
                'type' => 'income',
                'is_default' => false
            ]);
        }
        
        // Birthday gifts (random month)
        if (rand(1, 12) == $month) {
            $giftDay = $this->pickDay(1, $daysInMonth);
            $giftAmount = rand(100000, 1000000);
            
            $this->createTransaction($user, $giftCategory, 'income', $giftAmount, 'Hadiah Ulang Tahun', $year, $month, $giftDay, '18:00');
        }
        
        // Wedding gifts (random)
        if (rand(1, 20) == 1) { // 5% chance
            $giftDay = $this->pickDay(1, $daysInMonth);
            $giftAmount = rand(500000, 2000000);
            
            $this->createTransaction($user, $giftCategory, 'income', $giftAmount, 'Hadiah Pernikahan', $year, $month, $giftDay, '19:00');
        }
        
        // Other gifts (random)
        if (rand(1, 8) == 1) { // 12.5% chance
            $giftDay = $this->pickDay(1, $daysInMonth);
            $giftAmount = rand(50000, 300000);
            $giftTypes = [
                'Hadiah Natal',
                'Hadiah Lebaran',
                'Hadiah Kerja',
                'Hadiah Client',
                'Hadiah Teman',
                'Hadiah Keluarga',
                'Hadiah Spesial',
                'Hadiah Prestasi'
            ];
            
            $this->createTransaction($user, $giftCategory, 'income', $giftAmount, $giftTypes[array_rand($giftTypes)], $year, $month, $giftDay, '20:00');
        }
    }

    private function createTransaction(User $user, Category $category, string $type, int $amount, string $description, int $year, int $month, int $day, string $time)
    {
        // Get user's default account or first available account
        $account = $user->defaultAccount ?? $user->activeAccounts()->first();
        
        if (!$account) {
            echo "No account found for user {$user->name}, skipping transaction\n";
            return;
        }
        
        Transaction::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => $type,
            'amount' => $amount,
            'description' => $description,
            'notes' => 'Transaksi bulan ' . date('M Y', mktime(0, 0, 0, $month, 1, $year)),
            'transaction_date' => date('Y-m-d', mktime(0, 0, 0, $month, $day, $year)),
            'transaction_time' => $time,
        ]);
    }

    private function pickDay(int $start, int $daysInMonth): int
    {
        $start = max(1, $start);
        $start = min($start, $daysInMonth);
        return rand($start, $daysInMonth);
    }

    private function getRandomNotes(string $description): string
    {
        $notes = [
            'Pendapatan rutin bulanan',
            'Bonus kinerja yang memuaskan',
            'Return investasi yang stabil',
            'Project freelance yang sukses',
            'Refund yang sudah lama ditunggu',
            'Hadiah dari keluarga tercinta',
            'Pendapatan tambahan yang tidak terduga',
            'Komisi dari kerja keras',
            'Dividen dari investasi jangka panjang',
            'Pendapatan dari hobi yang menghasilkan'
        ];
        
        return $notes[array_rand($notes)];
    }
}
