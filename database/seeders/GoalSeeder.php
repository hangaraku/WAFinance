<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Goal;
use App\Models\User;
use Carbon\Carbon;

class GoalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        
        foreach ($users as $user) {
            $this->createGoalsForUser($user);
        }
    }

    private function createGoalsForUser(User $user)
    {
        $goals = [
            [
                'name' => 'Dana Darurat',
                'description' => 'Tabungan untuk kebutuhan darurat 6 bulan gaji',
                'target_amount' => 30000000,
                'current_amount' => rand(5000000, 15000000),
                'target_date' => Carbon::now()->addMonths(rand(6, 18)),
                'is_completed' => false,
            ],
            [
                'name' => 'DP Rumah',
                'description' => 'Down payment untuk rumah impian',
                'target_amount' => 100000000,
                'current_amount' => rand(10000000, 40000000),
                'target_date' => Carbon::now()->addMonths(rand(12, 36)),
                'is_completed' => false,
            ],
            [
                'name' => 'Liburan Keluarga',
                'description' => 'Tabungan untuk liburan keluarga ke Bali',
                'target_amount' => 15000000,
                'current_amount' => rand(2000000, 8000000),
                'target_date' => Carbon::now()->addMonths(rand(3, 12)),
                'is_completed' => false,
            ],
            [
                'name' => 'Gadget Baru',
                'description' => 'Laptop baru untuk kerja dan kuliah',
                'target_amount' => 12000000,
                'current_amount' => rand(1000000, 6000000),
                'target_date' => Carbon::now()->addMonths(rand(2, 8)),
                'is_completed' => false,
            ],
            [
                'name' => 'Investasi Saham',
                'description' => 'Modal awal untuk investasi saham',
                'target_amount' => 5000000,
                'current_amount' => rand(500000, 2500000),
                'target_date' => Carbon::now()->addMonths(rand(3, 10)),
                'is_completed' => false,
            ],
            [
                'name' => 'Motor Baru',
                'description' => 'Motor baru untuk transportasi',
                'target_amount' => 25000000,
                'current_amount' => rand(3000000, 12000000),
                'target_date' => Carbon::now()->addMonths(rand(6, 18)),
                'is_completed' => false,
            ],
            [
                'name' => 'Pendidikan Anak',
                'description' => 'Biaya pendidikan anak untuk tahun depan',
                'target_amount' => 8000000,
                'current_amount' => rand(1000000, 4000000),
                'target_date' => Carbon::now()->addMonths(rand(4, 12)),
                'is_completed' => false,
            ],
        ];

        // Randomly select 3-5 goals for each user
        $selectedGoals = collect($goals)->random(rand(3, 5));
        
        foreach ($selectedGoals as $goalData) {
            Goal::create([
                'user_id' => $user->id,
                'name' => $goalData['name'],
                'description' => $goalData['description'],
                'target_amount' => $goalData['target_amount'],
                'current_amount' => $goalData['current_amount'],
                'target_date' => $goalData['target_date'],
                'is_completed' => $goalData['is_completed'],
            ]);
        }
    }
}
