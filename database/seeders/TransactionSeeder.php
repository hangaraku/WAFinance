<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\Account;
use Carbon\Carbon;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        
        foreach ($users as $user) {
            echo "Creating transactions for user: {$user->name}\n";
            
            // Get user accounts and categories
            $accounts = $user->accounts()->where('is_active', true)->get();
            $categories = $user->categories()->where('type', 'expense')->get();
            
            if ($accounts->isEmpty() || $categories->isEmpty()) {
                echo "Skipping user {$user->name} - no accounts or categories found\n";
                continue;
            }
            
            // Create expense transactions for the last 3 months
            $this->createExpenseTransactions($user, $accounts, $categories);
            
            // Create transfer transactions
            $this->createTransferTransactions($user, $accounts);
        }
        
        echo "Transaction seeding completed!\n";
    }
    
    private function createExpenseTransactions(User $user, $accounts, $categories)
    {
        $currentDate = Carbon::now();
        
        for ($monthOffset = 0; $monthOffset < 3; $monthOffset++) {
            $month = $currentDate->copy()->subMonths($monthOffset);
            $daysInMonth = $month->daysInMonth;
            
            // Create 15-25 random expense transactions per month
            $numTransactions = rand(15, 25);
            
            for ($i = 0; $i < $numTransactions; $i++) {
                $day = rand(1, $daysInMonth);
                $amount = rand(50000, 500000);
                $category = $categories->random();
                $account = $accounts->random();
                
                Transaction::create([
                    'user_id' => $user->id,
                    'account_id' => $account->id,
                    'category_id' => $category->id,
                    'type' => 'expense',
                    'amount' => $amount,
                    'description' => $this->generateExpenseDescription($category->name),
                    'notes' => 'Transaksi bulan ' . $month->format('M Y'),
                    'transaction_date' => $month->copy()->setDay($day),
                    'transaction_time' => $this->generateRandomTime()
                ]);
            }
            
            echo "Created {$numTransactions} expense transactions for {$month->format('M Y')}\n";
        }
    }
    
    private function createTransferTransactions(User $user, $accounts)
    {
        $currentDate = Carbon::now();
        
        for ($monthOffset = 0; $monthOffset < 3; $monthOffset++) {
            $month = $currentDate->copy()->subMonths($monthOffset);
            $daysInMonth = $month->daysInMonth;
            
            // Create 3-8 transfer transactions per month
            $numTransfers = rand(3, 8);
            
            for ($i = 0; $i < $numTransfers; $i++) {
                $day = rand(1, $daysInMonth);
                $amount = rand(100000, 2000000);
                
                // Get two different accounts for transfer
                $sourceAccount = $accounts->random();
                $destinationAccount = $accounts->where('id', '!=', $sourceAccount->id)->random();
                
                Transaction::create([
                    'user_id' => $user->id,
                    'account_id' => $sourceAccount->id,
                    'transfer_account_id' => $destinationAccount->id,
                    'category_id' => null, // Transfers don't need categories
                    'type' => 'transfer',
                    'amount' => $amount,
                    'description' => "Transfer dari {$sourceAccount->name} ke {$destinationAccount->name}",
                    'notes' => 'Transfer antar rekening',
                    'transaction_date' => $month->copy()->setDay($day),
                    'transaction_time' => $this->generateRandomTime()
                ]);
            }
            
            echo "Created {$numTransfers} transfer transactions for {$month->format('M Y')}\n";
        }
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
