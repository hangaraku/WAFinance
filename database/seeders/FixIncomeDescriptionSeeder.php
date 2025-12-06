<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Transaction;
use App\Models\Category;

class FixIncomeDescriptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Fix all income transactions with empty descriptions
        $incomeTransactions = Transaction::where('type', 'income')
            ->where(function($query) {
                $query->whereNull('description')
                      ->orWhere('description', '')
                      ->orWhere('description', ' ')
                      ->orWhere('description', '""')
                      ->orWhereRaw('LENGTH(TRIM(description)) = 0');
            })
            ->with('category')
            ->get();
        
        echo "Found " . $incomeTransactions->count() . " income transactions with empty descriptions\n";
        
        foreach ($incomeTransactions as $transaction) {
            $description = $this->generateDescription($transaction->category->name);
            $transaction->update(['description' => $description]);
            echo "Fixed transaction ID {$transaction->id}: {$description}\n";
        }
        
        // Also update transactions that have description as null (new column)
        $nullDescriptionTransactions = Transaction::where('type', 'income')
            ->whereNull('description')
            ->with('category')
            ->get();
        
        echo "Found " . $nullDescriptionTransactions->count() . " income transactions with null descriptions\n";
        
        foreach ($nullDescriptionTransactions as $transaction) {
            $description = $this->generateDescription($transaction->category->name);
            $transaction->update(['description' => $description]);
            echo "Fixed transaction ID {$transaction->id}: {$description}\n";
        }
        
        echo "All income transaction descriptions have been fixed!\n";
    }
    
    private function generateDescription(string $categoryName): string
    {
        return match ($categoryName) {
            'Gaji' => $this->getRandomSalaryDescription(),
            'Bonus' => $this->getRandomBonusDescription(),
            'Investasi' => $this->getRandomInvestmentDescription(),
            'Side Hustle' => $this->getRandomSideHustleDescription(),
            'Refund' => $this->getRandomRefundDescription(),
            'Hadiah' => $this->getRandomGiftDescription(),
            default => 'Pendapatan ' . $categoryName
        };
    }
    
    private function getRandomSalaryDescription(): string
    {
        $descriptions = [
            'Gaji Pokok Bulanan',
            'Gaji + Tunjangan',
            'Salary Transfer',
            'Gaji Bersih',
            'Gaji Pokok + Tunjangan',
            'Gaji Bulanan',
            'Gaji Pokok',
            'Gaji + Bonus'
        ];
        
        return $descriptions[array_rand($descriptions)];
    }
    
    private function getRandomBonusDescription(): string
    {
        $descriptions = [
            'Bonus Performa Bulanan',
            'Bonus Target Sales',
            'Bonus Project Selesai',
            'Bonus Kerja Lembur',
            'Bonus Kinerja Tim',
            'Bonus Tahunan',
            'Bonus Khusus',
            'Bonus Prestasi'
        ];
        
        return $descriptions[array_rand($descriptions)];
    }
    
    private function getRandomInvestmentDescription(): string
    {
        $descriptions = [
            'Return Investasi Saham',
            'Dividen Saham Blue Chip',
            'Profit Trading Forex',
            'Return Reksadana',
            'Bunga Deposito',
            'Return Crypto',
            'Profit Jual Saham',
            'Dividen Kuartalan',
            'Return Investasi',
            'Dividen Saham'
        ];
        
        return $descriptions[array_rand($descriptions)];
    }
    
    private function getRandomSideHustleDescription(): string
    {
        $descriptions = [
            'Project Freelance Web',
            'Design Logo Client',
            'Jasa Konsultasi IT',
            'Project Mobile App',
            'Jasa Writing Artikel',
            'Project Video Editing',
            'Jasa Photography',
            'Project Social Media',
            'Penjualan Online Shop',
            'Komisi Affiliate',
            'Pendapatan YouTube',
            'Pendapatan Blog'
        ];
        
        return $descriptions[array_rand($descriptions)];
    }
    
    private function getRandomRefundDescription(): string
    {
        $descriptions = [
            'Pajak Refund',
            'Refund Belanja Online',
            'Refund Tiket Pesawat',
            'Refund Hotel',
            'Refund Asuransi',
            'Refund Kartu Kredit',
            'Refund Membership',
            'Refund Subscription'
        ];
        
        return $descriptions[array_rand($descriptions)];
    }
    
    private function getRandomGiftDescription(): string
    {
        $descriptions = [
            'Hadiah Ulang Tahun',
            'Hadiah Pernikahan',
            'Hadiah Natal',
            'Hadiah Lebaran',
            'Hadiah Kerja',
            'Hadiah Client',
            'Hadiah Teman',
            'Hadiah Keluarga'
        ];
        
        return $descriptions[array_rand($descriptions)];
    }
}
