<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AIService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class TestAIChatInteractive extends Command
{
    protected $signature = 'ai:chat 
                          {--user=galih@baikfinansial.com : User email}
                          {--clear : Clear conversation history}';

    protected $description = 'Interactive AI chat test';

    public function handle()
    {
        $userEmail = $this->option('user');

        // Get user
        $user = User::where('email', $userEmail)->first();
        if (!$user) {
            $this->error("User with email {$userEmail} not found.");
            return 1;
        }

        // Clear history if requested
        if ($this->option('clear')) {
            Cache::forget("ai_conversation_{$user->id}");
            $this->info("✓ Conversation history cleared");
        }

        $this->info("=== AI Chat Test (Interactive) ===");
        $this->info("User: {$user->name} ({$user->email})");
        $this->info("Type 'exit' or 'quit' to end the session");
        $this->info("Type 'clear' to clear conversation history");
        $this->info("Type 'balance' to check current account balances");
        $this->line(str_repeat('=', 60));
        $this->newLine();

        $aiService = app(AIService::class);

        while (true) {
            $message = $this->ask('You');
            
            if (empty($message)) {
                continue;
            }

            $messageLower = strtolower(trim($message));

            if (in_array($messageLower, ['exit', 'quit', 'bye'])) {
                $this->info("Goodbye!");
                break;
            }

            if ($messageLower === 'clear') {
                Cache::forget("ai_conversation_{$user->id}");
                $this->info("✓ Conversation history cleared");
                $this->newLine();
                continue;
            }

            if ($messageLower === 'balance') {
                $this->showBalances($user);
                $this->newLine();
                continue;
            }

            try {
                $response = $aiService->processMessage($user, $message, [
                    'timestamp' => now()->toISOString()
                ]);

                if ($response['error']) {
                    $this->error("Error: " . $response['response']);
                } else {
                    $this->info("AI: " . $response['response']);
                }
                
                $this->newLine();

            } catch (\Exception $e) {
                $this->error("Exception: " . $e->getMessage());
                $this->newLine();
            }
        }

        return 0;
    }

    protected function showBalances(User $user)
    {
        $user->load('accounts');
        $this->info("Current Account Balances:");
        foreach ($user->accounts as $account) {
            $balance = number_format($account->balance, 0, ',', '.');
            $this->line("  - {$account->name}: Rp {$balance}");
        }
    }
}
