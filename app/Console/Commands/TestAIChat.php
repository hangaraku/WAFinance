<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AIService;
use App\Models\User;
use Carbon\Carbon;

class TestAIChat extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:test {message} {--user=galih@baikfinansial.com} {--timestamp=} {--timezone=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test AI chat processing with a message';

    /**
     * Execute the console command.
     */
    public function handle(AIService $aiService)
    {
        $userEmail = $this->option('user');
        $message = $this->argument('message');
        $timezone = $this->option('timezone') ?: config('app.timezone', 'Asia/Jakarta');
        $timestamp = $this->option('timestamp') ?: Carbon::now($timezone)->format('Y-m-d\TH:i:s.uP');
        
        // Find user
        $user = User::where('email', $userEmail)->first();
        
        if (!$user) {
            $this->error("User not found: {$userEmail}");
            $this->info("Available users:");
            User::all()->each(function($u) {
                $this->line("  - {$u->email} (ID: {$u->id}, Name: {$u->name})");
            });
            return 1;
        }
        
        $this->info("Testing AI with user: {$user->name} ({$user->email})");
        $this->info("Message: {$message}");
        $this->info("Timestamp: {$timestamp}");
        $this->line(str_repeat('-', 80));
        
        // Show user's categories and accounts for reference
        $this->newLine();
        $this->info("User's Categories:");
        $user->load('categories');
        if ($user->categories->isEmpty()) {
            $this->warn("  No categories found!");
        } else {
            $user->categories->each(function($cat) {
                $this->line("  - {$cat->name} ({$cat->type}) [ID: {$cat->id}]");
            });
        }
        
        $this->newLine();
        $this->info("User's Accounts:");
        $user->load('accounts');
        if ($user->accounts->isEmpty()) {
            $this->warn("  No accounts found!");
        } else {
            $user->accounts->each(function($acc) {
                $this->line("  - {$acc->name} ({$acc->type}) [ID: {$acc->id}] Balance: Rp " . number_format($acc->balance, 0, ',', '.'));
            });
        }
        
        $this->line(str_repeat('-', 80));
        $this->newLine();
        
        // Process message
        $this->info("Processing message...");
        $this->newLine();
        
        try {
            $context = [
                'timestamp' => $timestamp,
                'timezone' => $timezone,
                'platform' => 'cli-test'
            ];
            
            $response = $aiService->processMessage($user, $message, $context);
            
            if ($response['error']) {
                $this->error("Error occurred:");
                $this->error($response['response']);
                return 1;
            }
            
            $this->info("AI Response:");
            $this->line($response['response']);
            $this->newLine();
            
            $this->info("Response Details:");
            $this->line("Model: {$response['model']}");
            $this->line("Timestamp: {$response['timestamp']}");
            
            if (isset($response['usage'])) {
                $this->line("Usage: " . json_encode($response['usage']));
            }
            
            $this->newLine();
            $this->info("✓ Test completed successfully!");
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("Exception occurred:");
            $this->error($e->getMessage());
            $this->newLine();
            $this->error("Stack trace:");
            $this->line($e->getTraceAsString());
            return 1;
        }
    }
}
