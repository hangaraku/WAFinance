<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;

class CountSystemPromptTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:count-system-prompt-tokens';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Estimate token count for resources/ai/system_prompt.txt (simple heuristics)';

    public function handle(): int
    {
        $path = resource_path('ai/system_prompt.txt');

        if (!file_exists($path)) {
            $this->error("System prompt file not found: {$path}");
            return 1;
        }

        $template = file_get_contents($path);

        $now = Carbon::now();
        $replacements = [
            '{{TIMESTAMP}}' => $now->toIso8601String(),
            '{{DATE_STR}}' => $now->format('l, F j, Y'),
            '{{TIME_STR}}' => $now->format('H:i'),
        ];

        $prompt = str_replace(array_keys($replacements), array_values($replacements), $template);

        // Normalize whitespace
        $prompt = preg_replace('/\r\n?/', "\n", $prompt);

        // Basic metrics
        $bytes = mb_strlen($prompt, '8bit');
        $wordCount = str_word_count(strip_tags($prompt));

        // Heuristic token estimators
        $tokens_by_bytes = (int) ceil($bytes / 4); // common heuristic: ~4 bytes/token
        $tokens_by_words = (int) ceil($wordCount * 1.33); // heuristic: ~1.33 tokens/word

        $avgEstimate = (int) ceil(($tokens_by_bytes + $tokens_by_words) / 2);

        $this->info("System prompt: {$path}");
        $this->line("Bytes: {$bytes}");
        $this->line("Words: {$wordCount}");
        $this->line("Estimated tokens (bytes / 4 heuristic): {$tokens_by_bytes}");
        $this->line("Estimated tokens (words * 1.33 heuristic): {$tokens_by_words}");
        $this->line("Average estimated tokens: {$avgEstimate}");

        $this->line('');
        $this->line('Note: These are heuristic estimates. For exact counts use a tokenizer compatible with your model (tiktoken or equivalent).');

        return 0;
    }
}
