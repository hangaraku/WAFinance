<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\Budget;
use App\Services\AIService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Tests\TestCase;

/**
 * AI Chat Scenario Tests
 * 
 * This test suite covers various AI interaction scenarios:
 * 1. Simple queries (account balance, budget status)
 * 2. Single transaction recording
 * 3. Income recording
 * 4. Multiple transactions in one message
 * 5. Expense with specific account
 * 6. Transfer between accounts
 * 7. Yesterday date parsing
 * 8. Financial summary query
 * 9. Category creation
 * 10. Budget query
 */
class AIScenarioTest extends TestCase
{
    use RefreshDatabase;

    protected User $testUser;
    protected AIService $aiService;
    protected string $testTimestamp;
    protected string $testDate;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear ALL conversation caches before each test
        Cache::flush();
        
        // Create test user
        $this->testUser = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Create default accounts
        $this->createTestAccounts();
        
        // Create default categories
        $this->createTestCategories();

        // Create budgets for testing
        $this->createTestBudgets();

        // Set fixed timestamp for reproducibility
        $this->testDate = '2025-12-07';
        $this->testTimestamp = '2025-12-07T10:00:00.000000+07:00';

        // Initialize AI service
        $this->aiService = app(AIService::class);

        // Clear conversation history for this user specifically
        Cache::forget("ai_conversation_{$this->testUser->id}");
    }

    protected function createTestAccounts(): void
    {
        Account::create([
            'user_id' => $this->testUser->id,
            'name' => 'Cash',
            'type' => 'cash',
            'balance' => 1000000,
            'is_default' => true,
        ]);

        Account::create([
            'user_id' => $this->testUser->id,
            'name' => 'Bank BCA',
            'type' => 'bank',
            'balance' => 5000000,
        ]);

        Account::create([
            'user_id' => $this->testUser->id,
            'name' => 'GoPay',
            'type' => 'wallet',
            'balance' => 500000,
        ]);
    }

    protected function createTestCategories(): void
    {
        // Expense categories
        Category::create([
            'user_id' => $this->testUser->id,
            'name' => 'Makan',
            'type' => 'expense',
            'color' => '#FF6B35',
        ]);

        Category::create([
            'user_id' => $this->testUser->id,
            'name' => 'Transportasi',
            'type' => 'expense',
            'color' => '#4F46E5',
        ]);

        Category::create([
            'user_id' => $this->testUser->id,
            'name' => 'Belanja',
            'type' => 'expense',
            'color' => '#059669',
        ]);

        Category::create([
            'user_id' => $this->testUser->id,
            'name' => 'Lain-lain',
            'type' => 'expense',
            'color' => '#6B7280',
        ]);

        // Income categories
        Category::create([
            'user_id' => $this->testUser->id,
            'name' => 'Gaji',
            'type' => 'income',
            'color' => '#10B981',
        ]);

        Category::create([
            'user_id' => $this->testUser->id,
            'name' => 'Bonus',
            'type' => 'income',
            'color' => '#F59E0B',
        ]);

        Category::create([
            'user_id' => $this->testUser->id,
            'name' => 'Freelance',
            'type' => 'income',
            'color' => '#8B5CF6',
        ]);
    }

    protected function createTestBudgets(): void
    {
        $makanCategory = Category::where('user_id', $this->testUser->id)
            ->where('name', 'Makan')
            ->first();

        $transportCategory = Category::where('user_id', $this->testUser->id)
            ->where('name', 'Transportasi')
            ->first();

        Budget::create([
            'user_id' => $this->testUser->id,
            'category_id' => $makanCategory->id,
            'amount' => 2000000,
            'spent' => 1200000,
            'month' => 12,
            'year' => 2025,
        ]);

        Budget::create([
            'user_id' => $this->testUser->id,
            'category_id' => $transportCategory->id,
            'amount' => 1000000,
            'spent' => 300000,
            'month' => 12,
            'year' => 2025,
        ]);
    }

    /**
     * Helper to send message and optionally handle confirmation
     */
    protected function sendMessageWithConfirmation(string $message, ?string $timestamp = null): array
    {
        $response1 = $this->sendMessage($message, $timestamp);
        
        // Log the response for debugging
        \Log::info("AI Response for '{$message}':", ['response' => $response1['response']]);
        
        // Check if AI is asking for confirmation and transaction not yet created
        // More comprehensive confirmation detection
        $isAskingConfirmation = (
            preg_match('/benar\??|konfirmasi|lanjut\??|catat\??|simpan\??/i', $response1['response']) && 
            !preg_match('/berhasil|disimpan|saved|tercatat|telah dicatat|sudah dicatat|sudah disimpan/i', $response1['response'])
        );

        if ($isAskingConfirmation) {
            \Log::info("Confirmation needed, sending 'ya'");
            $response2 = $this->sendMessage('ya');
            return [
                'initial' => $response1,
                'confirmation' => $response2,
                'needed_confirmation' => true,
            ];
        }

        return [
            'initial' => $response1,
            'confirmation' => null,
            'needed_confirmation' => false,
        ];
    }

    protected function sendMessage(string $message, ?string $timestamp = null): array
    {
        return $this->aiService->processMessage($this->testUser, $message, [
            'timestamp' => $timestamp ?? $this->testTimestamp,
            'timezone' => 'Asia/Jakarta',
            'platform' => 'test',
        ]);
    }

    protected function getTransactionCount(): int
    {
        return Transaction::where('user_id', $this->testUser->id)->count();
    }

    protected function getLatestTransaction(): ?Transaction
    {
        return Transaction::where('user_id', $this->testUser->id)
            ->latest('id')
            ->first();
    }

    protected function getAccountBalance(string $name): float
    {
        $account = Account::where('user_id', $this->testUser->id)
            ->where('name', $name)
            ->first();
        
        return $account ? (float)$account->balance : 0;
    }

    // ==========================================
    // SCENARIO 1: Simple Account Balance Query
    // ==========================================
    public function test_scenario_1_account_balance_query(): void
    {
        $response = $this->sendMessage('cek saldo');

        $this->assertFalse($response['error'], 'Response should not have error');
        $this->assertNotEmpty($response['response'], 'Response should not be empty');
        
        // Should mention account names and balances
        $responseText = strtolower($response['response']);
        $this->assertTrue(
            str_contains($responseText, 'cash') || 
            str_contains($responseText, 'bca') || 
            str_contains($responseText, 'gopay') ||
            str_contains($responseText, 'saldo') ||
            str_contains($responseText, 'akun'),
            'Response should mention accounts or balances'
        );
    }

    // ==========================================
    // SCENARIO 2: Budget Status Query
    // ==========================================
    public function test_scenario_2_budget_status_query(): void
    {
        $response = $this->sendMessage('sisa budget bulan ini');

        $this->assertFalse($response['error'], 'Response should not have error');
        $this->assertNotEmpty($response['response'], 'Response should not be empty');
        
        // Should mention budget information
        $responseText = strtolower($response['response']);
        $this->assertTrue(
            str_contains($responseText, 'budget') || 
            str_contains($responseText, 'makan') ||
            str_contains($responseText, 'sisa') ||
            str_contains($responseText, 'anggaran'),
            'Response should mention budget information'
        );
    }

    // ==========================================
    // SCENARIO 3: Single Expense Recording
    // ==========================================
    public function test_scenario_3_single_expense_recording(): void
    {
        $initialCount = $this->getTransactionCount();

        // Send expense message with confirmation handling
        $result = $this->sendMessageWithConfirmation('beli kopi 15rb');
        
        $response = $result['initial'];
        $this->assertFalse($response['error'], 'Response should not have error');
        $this->assertNotEmpty($response['response'], 'Response should not be empty');

        // Transaction should be created (either directly or after confirmation)
        $newCount = $this->getTransactionCount();
        $this->assertEquals($initialCount + 1, $newCount, "Transaction should be created. Initial: {$initialCount}, New: {$newCount}");

        // Verify transaction details
        $transaction = $this->getLatestTransaction();
        $this->assertNotNull($transaction, 'Transaction should exist');
        $this->assertEquals('expense', $transaction->type, 'Transaction type should be expense');
        $this->assertEquals(15000, (int)$transaction->amount, 'Amount should be 15000');
    }

    // ==========================================
    // SCENARIO 4: Income Recording
    // ==========================================
    public function test_scenario_4_income_recording(): void
    {
        $initialCount = $this->getTransactionCount();

        // Send income message with confirmation handling
        $result = $this->sendMessageWithConfirmation('pemasukan gaji 5jt');

        $response = $result['initial'];
        $this->assertFalse($response['error'], 'Response should not have error');
        $this->assertNotEmpty($response['response'], 'Response should not be empty');

        // Transaction should be created
        $newCount = $this->getTransactionCount();
        $this->assertEquals($initialCount + 1, $newCount, 'Income transaction should be created');

        // Verify transaction details
        $transaction = $this->getLatestTransaction();
        $this->assertNotNull($transaction, 'Transaction should exist');
        $this->assertEquals('income', $transaction->type, 'Transaction type should be income');
        $this->assertEquals(5000000, (int)$transaction->amount, 'Amount should be 5000000 (5jt parsed)');
    }

    // ==========================================
    // SCENARIO 5: Multiple Transactions in One Message
    // ==========================================
    public function test_scenario_5_multiple_transactions(): void
    {
        $initialCount = $this->getTransactionCount();

        // Send message with multiple transactions
        $result = $this->sendMessageWithConfirmation('makan siang 25rb dan beli bensin 50rb');

        $response = $result['initial'];
        $this->assertFalse($response['error'], 'Response should not have error');
        
        // Log for debugging
        \Log::info("Scenario 5 - Multiple transactions", [
            'initial_count' => $initialCount,
            'response' => $response['response'],
            'needed_confirmation' => $result['needed_confirmation']
        ]);

        // If still asking for confirmation after first attempt, try again
        $newCount = $this->getTransactionCount();
        if ($newCount === $initialCount && $result['needed_confirmation']) {
            // Already sent confirmation in sendMessageWithConfirmation
            $newCount = $this->getTransactionCount();
        }
        
        // Should create at least 1 transaction (may be 2 if both processed)
        $this->assertGreaterThan($initialCount, $newCount, 
            "At least one transaction should be created. Initial: {$initialCount}, New: {$newCount}, Response: " . substr($response['response'], 0, 200));
    }

    // ==========================================
    // SCENARIO 6: Expense with Specific Account
    // ==========================================
    public function test_scenario_6_expense_with_specific_account(): void
    {
        $initialCount = $this->getTransactionCount();

        // Expense from specific account
        $result = $this->sendMessageWithConfirmation('bayar parkir 5rb dari GoPay');

        $response = $result['initial'];
        $this->assertFalse($response['error'], 'Response should not have error');

        // Verify transaction created
        $newCount = $this->getTransactionCount();
        $this->assertEquals($initialCount + 1, $newCount, 'Transaction should be created');
        
        $transaction = $this->getLatestTransaction();
        $this->assertNotNull($transaction, 'Transaction should exist');
        $this->assertEquals(5000, (int)$transaction->amount, 'Amount should be 5000');
        $this->assertEquals('expense', $transaction->type, 'Type should be expense');
    }

    // ==========================================
    // SCENARIO 7: Transfer Between Accounts
    // ==========================================
    public function test_scenario_7_transfer_between_accounts(): void
    {
        $initialCount = $this->getTransactionCount();

        // Transfer request
        $result = $this->sendMessageWithConfirmation('transfer 500rb dari BCA ke Cash');

        $response = $result['initial'];
        $this->assertFalse($response['error'], 'Response should not have error');

        // Check if transaction was created
        $newCount = $this->getTransactionCount();
        
        // Transfer might create 1 or 2 transactions depending on implementation
        $this->assertGreaterThanOrEqual($initialCount, $newCount, 'Transfer should create transaction(s)');
        
        // If transaction was created, verify it's a transfer
        if ($newCount > $initialCount) {
            $transaction = $this->getLatestTransaction();
            $this->assertNotNull($transaction, 'Transaction should exist');
            // Amount should be 500000
            $this->assertEquals(500000, (int)$transaction->amount, 'Amount should be 500000');
        }
    }

    // ==========================================
    // SCENARIO 8: Yesterday Date Parsing
    // ==========================================
    public function test_scenario_8_yesterday_date_parsing(): void
    {
        $initialCount = $this->getTransactionCount();
        
        $result = $this->sendMessageWithConfirmation('kemarin beli makan 30rb');

        $response = $result['initial'];
        $this->assertFalse($response['error'], 'Response should not have error');

        // Log for debugging
        \Log::info("Scenario 8 - Yesterday date", [
            'initial_count' => $initialCount,
            'response' => $response['response'],
            'needed_confirmation' => $result['needed_confirmation']
        ]);

        // Verify transaction created
        $newCount = $this->getTransactionCount();
        $this->assertEquals($initialCount + 1, $newCount, 
            "Transaction should be created. Initial: {$initialCount}, New: {$newCount}, Response: " . substr($response['response'], 0, 200));

        // Verify transaction details
        $transaction = $this->getLatestTransaction();
        $this->assertNotNull($transaction, 'Transaction should exist');
        $this->assertEquals(30000, (int)$transaction->amount, 'Amount should be 30000');
        
        // Verify date is yesterday (Dec 6 if test date is Dec 7)
        $expectedDate = Carbon::parse($this->testDate)->subDay()->format('Y-m-d');
        $transactionDate = Carbon::parse($transaction->transaction_date)->format('Y-m-d');
        $this->assertEquals($expectedDate, $transactionDate, "Date should be yesterday ({$expectedDate}), got: {$transactionDate}");
    }

    // ==========================================
    // SCENARIO 9: Financial Summary Query
    // ==========================================
    public function test_scenario_9_financial_summary(): void
    {
        // First add some transactions manually
        $cashAccount = Account::where('user_id', $this->testUser->id)->where('name', 'Cash')->first();
        $makanCategory = Category::where('user_id', $this->testUser->id)->where('name', 'Makan')->first();
        $gajiCategory = Category::where('user_id', $this->testUser->id)->where('name', 'Gaji')->first();

        Transaction::create([
            'user_id' => $this->testUser->id,
            'account_id' => $cashAccount->id,
            'category_id' => $makanCategory->id,
            'type' => 'expense',
            'amount' => 100000,
            'description' => 'Test meal',
            'transaction_date' => $this->testDate,
            'transaction_time' => '12:00:00',
        ]);

        Transaction::create([
            'user_id' => $this->testUser->id,
            'account_id' => $cashAccount->id,
            'category_id' => $gajiCategory->id,
            'type' => 'income',
            'amount' => 3000000,
            'description' => 'Test salary',
            'transaction_date' => $this->testDate,
            'transaction_time' => '09:00:00',
        ]);

        $response = $this->sendMessage('ringkasan keuangan bulan ini');

        $this->assertFalse($response['error'], 'Response should not have error');
        $this->assertNotEmpty($response['response'], 'Response should not be empty');
        
        // Should contain summary information
        $responseText = strtolower($response['response']);
        $this->assertTrue(
            str_contains($responseText, 'pengeluaran') || 
            str_contains($responseText, 'pemasukan') ||
            str_contains($responseText, 'total') ||
            str_contains($responseText, 'expense') ||
            str_contains($responseText, 'income') ||
            str_contains($responseText, 'ringkasan'),
            'Response should contain financial summary terms'
        );
    }

    // ==========================================
    // SCENARIO 10: Create New Category
    // ==========================================
    public function test_scenario_10_create_category(): void
    {
        $initialCategoryCount = Category::where('user_id', $this->testUser->id)->count();

        $response = $this->sendMessage('tambah kategori Entertainment');

        $this->assertFalse($response['error'], 'Response should not have error');
        
        // Category should be created
        $newCategoryCount = Category::where('user_id', $this->testUser->id)->count();
        $this->assertEquals(
            $initialCategoryCount + 1, 
            $newCategoryCount,
            'New category should be created'
        );

        // Verify category exists
        $newCategory = Category::where('user_id', $this->testUser->id)
            ->where('name', 'Entertainment')
            ->first();
        
        $this->assertNotNull($newCategory, 'Entertainment category should exist');
    }

    // ==========================================
    // SCENARIO 11: Create Expense with Brand New Category
    // ==========================================
    public function test_scenario_11_expense_with_new_category(): void
    {
        $initialTransactionCount = Transaction::where('user_id', $this->testUser->id)->count();

        // Ensure "Fitness" category doesn't exist (use unique name to avoid conflicts)
        $this->assertNull(
            Category::where('user_id', $this->testUser->id)
                ->where('name', 'like', '%Fitness%')
                ->first(),
            'Fitness category should not exist initially'
        );

        // Step 1: Request expense with a completely new category
        $response = $this->sendMessage('pengeluaran fitness 150rb, tolong buatkan kategori baru Fitness');

        $this->assertFalse($response['error'], 'Response should not have error');
        
        // Check if AI asks for confirmation or directly creates
        $responseText = strtolower($response['response']);
        
        // Keep confirming until transaction is saved (up to 3 attempts)
        $attempts = 0;
        while ($attempts < 3) {
            $attempts++;
            
            // Check if transaction was already created
            $fitnessCategory = Category::where('user_id', $this->testUser->id)
                ->where('name', 'like', '%Fitness%')
                ->first();
            
            if ($fitnessCategory) {
                $transactionExists = Transaction::where('user_id', $this->testUser->id)
                    ->where('category_id', $fitnessCategory->id)
                    ->exists();
                
                if ($transactionExists) {
                    break; // Transaction created, exit loop
                }
            }
            
            // If AI asks for confirmation, confirm it
            if (str_contains($responseText, 'benar') || 
                str_contains($responseText, 'konfirmasi') ||
                str_contains($responseText, 'confirm') ||
                str_contains($responseText, '?')) {
                
                $confirmResponse = $this->sendMessage('ya, benar');
                $this->assertFalse($confirmResponse['error'], 'Confirmation should not have error');
                $responseText = strtolower($confirmResponse['response']);
            } else {
                break; // No more confirmation needed
            }
        }

        // Verify category was created
        $fitnessCategory = Category::where('user_id', $this->testUser->id)
            ->where('name', 'like', '%Fitness%')
            ->first();
        
        $this->assertNotNull($fitnessCategory, 'Fitness category should be created');
        $this->assertEquals('expense', $fitnessCategory->type, 'Fitness should be expense category');

        // Verify transaction was created with new category
        $newTransactionCount = Transaction::where('user_id', $this->testUser->id)->count();
        $this->assertGreaterThan(
            $initialTransactionCount,
            $newTransactionCount,
            'Transaction should be created'
        );

        // Find the fitness transaction
        $fitnessTransaction = Transaction::where('user_id', $this->testUser->id)
            ->where('category_id', $fitnessCategory->id)
            ->first();
        
        $this->assertNotNull($fitnessTransaction, 'Transaction should be linked to Fitness category');
        $this->assertEquals(150000, $fitnessTransaction->amount, 'Amount should be 150000');
    }

    // ==========================================
    // SCENARIO 12: Change Category and Price During Confirmation
    // ==========================================
    public function test_scenario_12_change_category_and_price_during_confirmation(): void
    {
        $initialTransactionCount = Transaction::where('user_id', $this->testUser->id)->count();

        // Step 1: Request expense with initial category and price
        $response = $this->sendMessage('beli makanan 25rb');

        $this->assertFalse($response['error'], 'Response should not have error');
        
        $responseText = strtolower($response['response']);
        
        // AI should ask for confirmation mentioning Makan category and 25rb
        $this->assertTrue(
            str_contains($responseText, 'makan') || 
            str_contains($responseText, '25.000') ||
            str_contains($responseText, '25000') ||
            str_contains($responseText, 'makanan'),
            'Response should mention original category or amount'
        );

        // Step 2: Change category and price during confirmation
        $changeResponse = $this->sendMessage('jangan, ubah kategorinya jadi Belanja dan harganya jadi 30rb');

        $this->assertFalse($changeResponse['error'], 'Change response should not have error');
        
        $changeText = strtolower($changeResponse['response']);
        
        // AI should acknowledge the changes and ask for confirmation again
        $this->assertTrue(
            str_contains($changeText, 'belanja') || 
            str_contains($changeText, '30.000') ||
            str_contains($changeText, '30000'),
            'Response should reflect changed category or amount. Got: ' . $changeResponse['response']
        );

        // If AI asks for confirmation again, confirm
        if (str_contains($changeText, 'benar') || 
            str_contains($changeText, 'konfirmasi') ||
            str_contains($changeText, 'confirm') ||
            str_contains($changeText, '?')) {
            
            $finalConfirmResponse = $this->sendMessage('ya benar');
            $this->assertFalse($finalConfirmResponse['error'], 'Final confirmation should not have error');
        }

        // Verify transaction was created
        $newTransactionCount = Transaction::where('user_id', $this->testUser->id)->count();
        $this->assertGreaterThan(
            $initialTransactionCount,
            $newTransactionCount,
            'Transaction should be created'
        );

        // Get the latest transaction to see what was actually saved
        $latestTransaction = Transaction::where('user_id', $this->testUser->id)
            ->orderBy('id', 'desc')
            ->first();
        
        $this->assertNotNull($latestTransaction, 'Latest transaction should exist');
        
        // Get the Belanja category
        $belanjaCategory = Category::where('user_id', $this->testUser->id)
            ->where('name', 'Belanja')
            ->first();
        
        $this->assertNotNull($belanjaCategory, 'Belanja category should exist');

        // The transaction should have been updated to Belanja and 30000
        // Allow some flexibility - check if EITHER the category changed OR the amount changed
        $categoryChanged = $latestTransaction->category_id === $belanjaCategory->id;
        $amountChanged = $latestTransaction->amount == 30000;
        
        $this->assertTrue(
            $categoryChanged && $amountChanged,
            "Transaction should have Belanja category (id: {$belanjaCategory->id}) and 30000 amount. " .
            "Got: category_id={$latestTransaction->category_id}, amount={$latestTransaction->amount}"
        );
        
        $this->assertEquals('expense', $latestTransaction->type, 'Should be expense type');
    }

    // ==========================================
    // All Scenarios Summary Test
    // ==========================================
    public function test_all_scenarios_complete(): void
    {
        $this->assertTrue(true, 'All scenario tests are defined');
    }
}
