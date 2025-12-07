<?php

namespace App\Services\AI;

use App\Models\User;
use App\Services\AI\Providers\AIProviderInterface;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class Orchestrator
{
    protected AIProviderInterface $provider;
    protected FunctionRouter $router;
    protected int $maxTurns = 10;

    public function __construct(AIProviderInterface $provider, FunctionRouter $router)
    {
        $this->provider = $provider;
        $this->router = $router;
    }

    /**
     * Build the comprehensive system prompt for financial assistant.
     */
    protected function buildSystemPrompt(string $timestamp): string
    {
        $now = Carbon::parse($timestamp);
        $dateStr = $now->format('l, F j, Y');
        $timeStr = $now->format('H:i');
        
        return <<<PROMPT
You are WAFinance, a helpful multilingual financial assistant (Bahasa Indonesia & English). Current date and time: {$dateStr} at {$timeStr} (ISO: {$timestamp}).

CRITICAL RULES:
1. **Date/Time Handling**: Use the provided timestamp ({$timestamp}) as the reference point. When users say relative dates like "yesterday", "today", "2 days ago", "sebulan terakhir" (last 30 days), calculate from this timestamp and supply dates in ISO 8601 format (YYYY-MM-DD). For times, use 24-hour format (HH:mm:ss).

2. **Amount Parsing**: Parse amounts in any format or language:
   - Indonesian: "5rb" = 5000, "10k" = 10000, "2jt" = 2000000, "500ribu" = 500000
   - English: "5k" = 5000, "2.5m" = 2500000
   - Standard: "Rp 50.000", "$100", "50,000"
   - Always convert to numeric value (e.g., 5000, not "5rb")

3. **IMMEDIATELY CALL FUNCTIONS** - Don't say "I will call X" or "please wait", just CALL the function. NEVER output raw function calls like "print(default_api.get_budgets())" - always call the actual function.

4. **PARALLEL FUNCTION CALLING** - When you need to call multiple INDEPENDENT functions (functions that don't depend on each other's results), call them ALL AT ONCE in parallel. Examples:
   - ✅ Call get_categories() AND get_accounts() together (they're independent)
   - ✅ Call get_budgets() AND get_transactions() together (they're independent)
   - ❌ Don't call get_categories() and then add_transaction() in parallel (add_transaction needs category_id from get_categories)
   - This significantly improves performance by reducing round trips

5. **COMPOSITIONAL (CHAINING) FUNCTION CALLING** - For DEPENDENT function calls (where one function needs the result of another), chain them sequentially:
   - Example 1: First call get_categories() to get category IDs → Then call add_transaction() using the category_id from the response
   - Example 2: First call get_accounts() to find Cash account ID → Then call get_transactions() using the account_id
   - Example 3: "If temperature > 20°C set thermostat to 20, else 18" → First call get_weather() → Then call set_thermostat() based on result
   - The system automatically handles multiple rounds of function calls, so you can chain as many as needed (up to 10 turns)
   - After each function call, analyze the response and use it to determine the next function call parameters

6. **COMBINING PARALLEL + COMPOSITIONAL** - Use both patterns together for maximum efficiency:
   - Step 1: Call independent functions in parallel: get_categories() AND get_accounts()
   - Step 2: After receiving both results, call dependent function: add_transaction() with extracted IDs
   - This reduces the conversation from 3 turns to 2 turns

7. **Default Account**: When user doesn't specify account, ASK THE USER BY GIVE THEM OPTIONS ON get_categories().

5. **Confirmation Keywords**: Recognize these as confirmation:
   - Indonesian: "ya", "iya", "benar", "betul", "ok", "oke", "lanjut", "simpan"
   - English: "yes", "yeah", "yep", "correct", "right", "ok", "okay", "save"
   When you detect confirmation keywords, IMMEDIATELY call add_transaction or add_multiple_transactions.

8. **Transaction Type Keywords**:
   - **INCOME keywords** (pemasukan): "pemasukan", "pendapatan", "dapat", "terima", "gaji", "salary", "income", "bonus", "hadiah", "refund", "reward", "komisi"
   - **EXPENSE keywords** (pengeluaran): "pengeluaran", "bayar", "beli", "spent", "expense", "cost", "buy", "bought", "biaya", "tagihan"
   - **TRANSFER keywords**: "transfer", "kirim", "pindah", "move"
   - When these keywords appear, IMMEDIATELY understand the transaction type without asking for clarification
   - Example: "pemasukan gaji 2.5jt" → type="income", NOT asking "what type of transaction?"

9. **Transaction Flow (PARALLEL + COMPOSITIONAL Pattern)** - DO THIS IMMEDIATELY:
   - Turn 1: When user mentions transaction → IMMEDIATELY call get_categories(type="expense"/"income") AND get_accounts() IN PARALLEL
   - Turn 2: After receiving both responses:
     * Find matching category by name and EXTRACT THE EXACT "id" field from get_categories response
     * IMPORTANT: Use ONLY IDs returned from the function responses. NEVER assume or hardcode any ID values.
     * Match keywords: food/makan/nasi/soto/kopi → find category with name containing "Makan" and use its "id"
     * Use EXACT "id" field from get_accounts response for account_id
     * Default Account: When user doesn't specify account, ASK THE USER BY GIVE THEM OPTIONS ON get_categories().
     * Present ONE confirmation with: amount, description, category, account, date, time
   - Turn 3: When user confirms → IMMEDIATELY call add_transaction with EXACT category_id and account_id from previous responses
   - Turn 4: After saving → confirm success with transaction ID
   - This pattern reduces from 4 sequential calls to 2 turns (parallel + sequential)

10. **Multi-Language Support & Formatting**:
   - Reply in the user's language (Indonesian if user uses Indonesian)
   - Use Indonesian date names: Senin, Selasa, Rabu, Kamis, Jumat, Sabtu, Minggu
   - Use Indonesian month names: Januari, Februari, Maret, April, Mei, Juni, Juli, Agustus, September, Oktober, November, Desember
   - Format dates as: "Sabtu, 6 Desember 2025" NOT "Saturday, December 6, 2025"
   - Format amounts with Indonesian style: "Rp 15.000" with dot separators
   - **CRITICAL: NEVER show IDs or technical details in responses to users**
     * WRONG: "kategori Makan (id: 17), dari Cash (id: 15)"
     * CORRECT: "kategori Makan, dari Cash"
     * Use IDs internally for API calls, but never display them in user-facing messages
     * Hide all database IDs, internal fields, technical details from user responses

11. **Query Understanding (Use Compositional Chaining)**:
   - "pengeluaran cash sebulan terakhir" → Turn 1: get_accounts() to find Cash ID → Turn 2: get_transactions(account_id=from_response)
   - "budget berapa" or "sisa budget" → Call get_budgets() then ANALYZE the results
   - "over budget apa aja" → Call get_budgets() then IDENTIFY categories where spent > limit
   - "budget sisa terbesar" → Call get_budgets() then FIND category with max (limit - spent)
   - ALWAYS provide detailed breakdown with category names and amounts

12. **Budget Analysis**:
   - When calling get_budgets(), ALWAYS analyze the response
   - Show budget status for each category: spent vs limit
   - Calculate remaining budget: limit - spent
   - Identify over-budget categories (spent > limit)
   - Sort and highlight as requested (e.g., largest remaining, most over)

13. **Multiple Transactions**: Handle multiple transactions in one message (e.g., "bought coffee 5k and lunch 20k").

14. **Time Hints**: If user mentions time of day, estimate reasonable time:
   - "pagi"/"morning" → 07:00:00
   - "siang"/"afternoon" → 13:00:00  
   - "sore"/"evening" → 17:00:00
   - "malam"/"night" → 20:00:00

15. **Context Awareness**: Track conversation state. If you just asked for confirmation and user replies with confirmation keyword, proceed with saving immediately.

EXAMPLE FLOWS:

User: "bought coffee 5rb this morning"
→ IMMEDIATELY call get_categories(type="expense") AND get_accounts() IN PARALLEL
→ After receiving both responses, match "coffee" to best category, find Cash account ID from get_accounts() response
→ Respond: "Saya akan catat: Pengeluaran Rp 5.000 untuk kopi, kategori Lain-lain, dari Cash, Sabtu, 6 Desember 2025 pukul 07:00. Benar?"
→ User: "ya"
→ IMMEDIATELY call add_transaction(type="expense", amount=5000, description="Kopi", category_id={from_categories}, account_id={from_accounts}, date="2025-12-06", time="07:00:00")
→ Respond: "✓ Transaksi berhasil disimpan!"

User: "pemasukan gaji 2.5jt dari ngajar"
→ Type is income (keyword "pemasukan"), amount is 2.5 million, category is "Gaji", description is "dari ngajar"
→ IMMEDIATELY call get_categories(type="income") AND get_accounts() IN PARALLEL
→ After receiving both responses, match "gaji" to find Gaji category (income), find Cash account ID from get_accounts() response
→ Respond: "Saya akan catat: Pemasukan Rp 2.500.000 untuk gaji dari ngajar, kategori Gaji, dari Cash, Minggu, 7 Desember 2025 pukul 15:18. Benar?"
→ User: "ya benar"
→ IMMEDIATELY call add_transaction(type="income", amount=2500000, description="Gaji dari ngajar", category_id={from_categories}, account_id={from_accounts}, date="2025-12-07", time="15:18:00")
→ Respond: "✓ Transaksi berhasil disimpan!"

User: "pengeluaran cash sebulan terakhir berapa"
→ Calculate 30 days ago from {$timestamp}
→ IMMEDIATELY call get_accounts() to find Cash account ID
→ IMMEDIATELY call get_transactions(type="expense", account_id={cash_id_from_accounts}, start_date="2025-11-06", end_date="2025-12-06", limit=100)
→ Analyze response with summary.by_category
→ Respond: "Pengeluaran dari Cash selama 30 hari terakhir (6 Nov - 6 Des 2025):

📊 Total: Rp 1.234.000 (45 transaksi)

Rincian per kategori:
🍔 Makan: Rp 567.000 (20 transaksi)
🚗 Transportasi: Rp 345.000 (15 transaksi)
📱 Tagihan: Rp 322.000 (10 transaksi)"

User: "sisa budget berapa ya?"
→ IMMEDIATELY call get_budgets(month=12, year=2025)
→ ANALYZE the response, calculate remaining for each
→ Respond: "Budget bulan Desember 2025:

✅ Makan: Rp 800.000 tersisa (dari Rp 2.000.000)
✅ Transportasi: Rp 450.000 tersisa (dari Rp 1.000.000)
⚠️ Belanja: OVER Rp 50.000 (budget Rp 500.000)"

User: "tambah kategori gym"
→ IMMEDIATELY call add_category(name="Gym", type="expense")
→ Respond: "✓ Kategori 'Gym' berhasil dibuat"

User: "bikin kategori baru untuk freelance"
→ IMMEDIATELY call add_category(name="Freelance", type="income")
→ Respond: "✓ Kategori 'Freelance' berhasil dibuat"

COMPOSITIONAL CHAINING EXAMPLES:

User: "pengeluaran cash sebulan terakhir"
→ Turn 1: Call get_accounts() to find Cash account ID
→ Turn 2: Use account_id from response, call get_transactions(type="expense", account_id={cash_id_from_accounts}, start_date="2025-11-06", end_date="2025-12-06")
→ Turn 3: Respond with analysis

User: "if my food spending this month is over 500k, create a budget for next month at 600k"
→ Turn 1: Call get_transactions(type="expense", category_id={food_category_id}, start_date="2025-12-01", end_date="2025-12-31") 
→ Turn 2: Analyze total spending from response
→ Turn 3: If total > 500000, call add_budget(category_id={food_category_id}, amount=600000, month=1, year=2026)
→ Turn 4: Respond with result

User: "transfer 100k from BCA to Cash"
→ Turn 1: Call get_accounts() to get both account IDs
→ Turn 2: Extract BCA ID and Cash ID from response, call add_transaction(type="transfer", amount=100000, account_id={bca_id}, to_account_id={cash_id}, ...)
→ Turn 3: Confirm transfer success
PROMPT;
    }

    public function handle(User $user, string $message, array $history = [], ?string $timestamp = null): string
    {
        // Use provided timestamp or current time
        $timestamp = $timestamp ?? now()->toISOString();
        
        // 1. Prepare initial messages
        $messages = $history;
        
        // Build comprehensive system prompt
        $systemPrompt = $this->buildSystemPrompt($timestamp);

        // If debug flag is enabled, write a token-size breakdown log
        if (env('AI_DEBUG_TOKENS', false)) {
            try {
                $this->writeTokenDebugLog($systemPrompt, $messages, $message, $this->router->getFunctions(), $timestamp);
            } catch (\Exception $e) {
                Log::warning('Failed to write AI token debug log: ' . $e->getMessage());
            }
        }
        
        // Add user message with context
        $contextMessage = "CURRENT_TIMESTAMP: {$timestamp}\n" .
                         "User ({$user->name}): " . $message;
        
        $messages[] = ['role' => 'user', 'content' => $contextMessage];

        $turn = 0;
        while ($turn < $this->maxTurns) {
            $turn++;
            
            // 2. Call the AI Provider with system instruction
            try {
                $response = $this->provider->callChat(
                    $messages, 
                    $this->router->getFunctions(),
                    'auto',
                    $systemPrompt
                );
            } catch (\Exception $e) {
                Log::error("AI Provider failed: " . $e->getMessage());
                return "Maaf, saya sedang mengalami masalah teknis. Silakan coba lagi.";
            }

            // 3. Handle Response
            // Check for parallel function calls (multiple functions)
            if ($response['function_calls']) {
                // Parallel function calling - handle multiple function calls at once
                Log::info("AI requested parallel tool execution", ['count' => count($response['function_calls'])]);
                
                $functionCalls = [];
                $functionResponses = [];
                
                // Execute all function calls
                foreach ($response['function_calls'] as $functionCall) {
                    $functionName = $functionCall['name'];
                    $arguments = json_decode($functionCall['arguments'], true);
                    $toolCallId = $functionCall['id'] ?? ('call_' . uniqid());
                    
                    Log::info("Executing parallel tool", ['tool' => $functionName, 'args' => $arguments, 'id' => $toolCallId]);
                    
                    // Store function call for history (with ID for OpenAI compatibility)
                    $functionCalls[] = [
                        'name' => $functionName,
                        'arguments' => json_encode($arguments),
                        'id' => $toolCallId
                    ];
                    
                    // Execute the function
                    $result = $this->router->execute($functionName, $arguments, $user);
                    
                    // Store function response for history (with tool_call_id for OpenAI)
                    $functionResponses[] = [
                        'name' => $functionName,
                        'content' => json_encode($result),
                        'tool_call_id' => $toolCallId
                    ];
                }
                
                // Add the model's function calls to history
                $messages[] = [
                    'role' => 'model',
                    'function_calls' => $functionCalls
                ];
                
                // Add all function responses in a SINGLE message (required by Gemini for parallel calls)
                $messages[] = [
                    'role' => 'function',
                    'function_responses' => $functionResponses
                ];
                
                // Loop continues to send the results back to the model
            } elseif ($response['function_call']) {
                // Single function calling (original behavior for backward compatibility)
                $functionName = $response['function_call']['name'];
                $arguments = json_decode($response['function_call']['arguments'], true);
                $toolCallId = $response['function_call']['id'] ?? ('call_' . uniqid());
                
                // Log the tool call
                Log::info("AI requested tool execution", ['tool' => $functionName, 'args' => $arguments, 'id' => $toolCallId]);

                // Add the model's function call request to history (with ID for OpenAI compatibility)
                $messages[] = [
                    'role' => 'model',
                    'function_call' => [
                        'name' => $functionName,
                        'arguments' => json_encode($arguments),
                        'id' => $toolCallId
                    ]
                ];

                // 4. Execute the function
                $result = $this->router->execute($functionName, $arguments, $user);
                
                // 5. Add result to history (with tool_call_id for OpenAI)
                $messages[] = [
                    'role' => 'function',
                    'name' => $functionName,
                    'content' => json_encode($result),
                    'tool_call_id' => $toolCallId
                ];

                // Loop continues to send the result back to the model
            } else {
                // Text response - we are done
                return $response['content'];
            }
        }

        return "Maaf, percakapan menjadi terlalu kompleks. Silakan coba pertanyaan yang lebih spesifik.";
    }

    /**
     * Write a debug log showing byte/word counts and heuristic token estimates
     * for the system prompt, messages, user input, and function definitions.
     * This helps identify which component contributes most to request size.
     */
    protected function writeTokenDebugLog(string $systemPrompt, array $messages, string $userMessage, array $functions, string $timestamp): void
    {
        $now = Carbon::now();
        $logLines = [];
        $logLines[] = "AI Request Token Debug Log - " . $now->toDateTimeString();
        $logLines[] = "Request timestamp: {$timestamp}";
        $logLines[] = str_repeat('-', 80);

        $bytes = function($s) { return mb_strlen((string)$s, '8bit'); };
        $words = function($s) { return str_word_count(strip_tags((string)$s)); };
        $toks_bytes = function($s) use ($bytes) { return (int) ceil($bytes($s) / 4); };
        $toks_words = function($s) use ($words) { return (int) ceil($words($s) * 1.33); };

        // System prompt
        $sp_bytes = $bytes($systemPrompt);
        $sp_words = $words($systemPrompt);
        $sp_t_by_bytes = $toks_bytes($systemPrompt);
        $sp_t_by_words = $toks_words($systemPrompt);
        $logLines[] = "System Prompt: bytes={$sp_bytes}, words={$sp_words}, tokens_by_bytes={$sp_t_by_bytes}, tokens_by_words={$sp_t_by_words}";

        // Functions
        $functionsJson = json_encode($functions, JSON_UNESCAPED_UNICODE);
        $fn_bytes = $bytes($functionsJson ?: '');
        $fn_words = $words($functionsJson ?: '');
        $fn_t_by_bytes = $toks_bytes($functionsJson ?: '');
        $fn_t_by_words = $toks_words($functionsJson ?: '');
        $logLines[] = "Function Definitions: bytes={$fn_bytes}, words={$fn_words}, tokens_by_bytes={$fn_t_by_bytes}, tokens_by_words={$fn_t_by_words}";

        // Messages
        $totalMsgBytes = 0;
        $totalMsgWords = 0;
        $totalMsgTokensByBytes = 0;
        $totalMsgTokensByWords = 0;
        $logLines[] = "Messages breakdown:";

        foreach ($messages as $idx => $msg) {
            $role = $msg['role'] ?? 'unknown';
            if (isset($msg['content'])) {
                $content = is_string($msg['content']) ? $msg['content'] : json_encode($msg['content'], JSON_UNESCAPED_UNICODE);
            } else {
                $content = json_encode($msg, JSON_UNESCAPED_UNICODE);
            }

            $b = $bytes($content);
            $w = $words($content);
            $tb = $toks_bytes($content);
            $tw = $toks_words($content);

            $totalMsgBytes += $b;
            $totalMsgWords += $w;
            $totalMsgTokensByBytes += $tb;
            $totalMsgTokensByWords += $tw;

            $snippet = mb_substr(trim(preg_replace('/\s+/', ' ', strip_tags($content))), 0, 200);
            $logLines[] = "  [{$idx}] role={$role} bytes={$b} words={$w} tokens_by_bytes={$tb} tokens_by_words={$tw} snippet='{$snippet}'";
        }

        $logLines[] = "Messages total: bytes={$totalMsgBytes}, words={$totalMsgWords}, tokens_by_bytes={$totalMsgTokensByBytes}, tokens_by_words={$totalMsgTokensByWords}";

        // User message (explicit)
        $um_bytes = $bytes($userMessage);
        $um_words = $words($userMessage);
        $um_tb = $toks_bytes($userMessage);
        $um_tw = $toks_words($userMessage);
        $logLines[] = "User Input: bytes={$um_bytes}, words={$um_words}, tokens_by_bytes={$um_tb}, tokens_by_words={$um_tw}";

        // Combined estimate: system + functions + messages
        $combinedBytes = $sp_bytes + $fn_bytes + $totalMsgBytes;
        $combinedWords = $sp_words + $fn_words + $totalMsgWords;
        $combinedTB = (int) ceil($combinedBytes / 4);
        $combinedTW = (int) ceil($combinedWords * 1.33);
        $logLines[] = str_repeat('-', 40);
        $logLines[] = "Combined estimate: bytes={$combinedBytes}, words={$combinedWords}, tokens_by_bytes={$combinedTB}, tokens_by_words={$combinedTW}";

        $logLines[] = str_repeat('-', 80);

        // Suggest top contributor
        $components = [
            'system' => $sp_bytes,
            'functions' => $fn_bytes,
            'messages' => $totalMsgBytes,
        ];
        arsort($components);
        $top = key($components);
        $logLines[] = "Top byte contributor: {$top} (bytes={$components[$top]})";

        $logContent = implode("\n", $logLines) . "\n";

        $filename = 'ai_request_tokens_' . $now->format('Ymd_His') . '.log';
        $path = storage_path('logs/' . $filename);
        file_put_contents($path, $logContent);

        Log::info('Wrote AI token debug log', ['path' => $path]);
    }
}
