# Function Calling Patterns in WAFinance AI

This document explains the two powerful function calling patterns implemented in WAFinance AI system, based on Gemini's native capabilities.

## Overview

WAFinance AI implements two advanced function calling patterns:

1. **Parallel Function Calling** - Execute multiple independent functions simultaneously
2. **Compositional Function Calling** - Chain dependent function calls sequentially

## 1. Parallel Function Calling

### What is it?
Parallel function calling allows the AI to invoke multiple independent functions at once, in a single turn. This significantly reduces latency and improves efficiency.

### When to use?
Use parallel calling when functions don't depend on each other's results.

### Examples

#### ✅ Good Use Cases

**Transaction Creation Flow:**
```
User: "bought coffee 5rb"
Turn 1: AI calls get_categories(type="expense") AND get_accounts() IN PARALLEL
Turn 2: AI uses results to call add_transaction()
```

**Budget & Transaction Analysis:**
```
User: "show my budget and recent transactions"
Turn 1: AI calls get_budgets() AND get_transactions() IN PARALLEL
Turn 2: AI analyzes both and responds
```

#### ❌ Bad Use Cases (Use Compositional Instead)

```
❌ Don't: get_categories() AND add_transaction() in parallel
   (add_transaction needs category_id from get_categories)

❌ Don't: get_accounts() AND get_transactions(account_id=?) in parallel
   (get_transactions needs account_id from get_accounts)
```

### Implementation

**GeminiProvider.php** detects multiple function calls:
```php
// Gemini returns multiple parts with functionCall
$parts = $data['candidates'][0]['content']['parts'];

// Parse all function calls
foreach ($parts as $part) {
    if (isset($part['functionCall'])) {
        $functionCalls[] = [
            'name' => $part['functionCall']['name'],
            'arguments' => json_encode($part['functionCall']['args'])
        ];
    }
}

// Return array of function calls
if (count($functionCalls) > 1) {
    return ['function_calls' => $functionCalls];
}
```

**Orchestrator.php** executes them:
```php
if ($response['function_calls']) {
    // Execute all functions
    foreach ($response['function_calls'] as $functionCall) {
        $result = $this->router->execute($functionName, $arguments, $user);
        $functionResponses[] = $result;
    }
    
    // Send all results back to AI
    // Loop continues for next turn
}
```

## 2. Compositional (Sequential) Function Calling

### What is it?
Compositional function calling allows the AI to chain multiple function calls where later calls depend on the results of earlier ones. The system automatically handles multiple conversation turns.

### When to use?
Use compositional calling when you need to:
- Use the result of one function as input to another
- Make conditional decisions based on function results
- Build complex workflows with multiple steps

### Examples

#### Example 1: Account ID Resolution
```
User: "show cash transactions last month"

Turn 1: AI calls get_accounts()
        Response: [{id: 15, name: "Cash", ...}, {id: 16, name: "Bank BCA", ...}]

Turn 2: AI extracts Cash ID (15) and calls:
        get_transactions(account_id=15, start_date="2025-11-06", end_date="2025-12-06")
        Response: [transactions...]

Turn 3: AI analyzes and responds with formatted result
```

#### Example 2: Conditional Logic
```
User: "if my food spending this month is over 500k, create a budget for next month at 600k"

Turn 1: AI calls get_transactions(type="expense", category_id=17, start_date="2025-12-01")
        Response: {transactions: [...], summary: {total: 567000}}

Turn 2: AI sees 567000 > 500000, so calls:
        add_budget(category_id=17, amount=600000, month=1, year=2026)
        Response: {success: true, budget_id: 42}

Turn 3: AI responds: "✓ Pengeluaran makanan bulan ini Rp 567.000 (melebihi Rp 500.000). 
        Budget bulan depan sudah dibuat: Rp 600.000"
```

#### Example 3: Transfer Between Accounts
```
User: "transfer 100k from BCA to Cash"

Turn 1: AI calls get_accounts()
        Response: [{id: 15, name: "Cash"}, {id: 16, name: "Bank BCA"}, ...]

Turn 2: AI extracts BCA (16) and Cash (15) IDs, calls:
        add_transaction(type="transfer", amount=100000, account_id=16, to_account_id=15, ...)
        Response: {success: true, transaction_id: 123}

Turn 3: AI confirms: "✓ Transfer Rp 100.000 dari Bank BCA ke Cash berhasil!"
```

### Implementation

The **while loop** in `Orchestrator.php` enables compositional calling:

```php
$turn = 0;
while ($turn < $this->maxTurns) {  // Up to 10 turns
    $turn++;
    
    // Call AI with current conversation history
    $response = $this->provider->callChat($messages, $functions, 'auto', $systemPrompt);
    
    // If AI returns function call
    if ($response['function_call']) {
        // Execute function
        $result = $this->router->execute($functionName, $arguments, $user);
        
        // Add function result to conversation history
        $messages[] = ['role' => 'function', 'name' => $functionName, 'content' => json_encode($result)];
        
        // Loop continues - AI can now use this result for next function call
    } else {
        // AI returned text response - we're done
        return $response['content'];
    }
}
```

Key points:
- Each function result is added to `$messages` history
- The loop continues, allowing AI to make another function call based on the result
- AI can chain up to 10 function calls (configurable via `$maxTurns`)
- The conversation naturally ends when AI returns text instead of a function call

## 3. Combining Both Patterns (Hybrid)

The most efficient approach combines both patterns:

### Example: Transaction Creation
```
User: "bought lunch 25k and coffee 5k"

Turn 1 (PARALLEL):
  - get_categories(type="expense") 
  - get_accounts()
  Executed simultaneously

Turn 2 (COMPOSITIONAL):
  AI receives both results, then calls:
  - add_multiple_transactions([
      {amount: 25000, category_id: 17, account_id: 15, description: "Lunch"},
      {amount: 5000, category_id: 17, account_id: 15, description: "Coffee"}
    ])

Turn 3: AI confirms success
```

**Efficiency gain:** 3 function calls completed in 2 turns instead of 4 turns.

### Example: Complex Query
```
User: "compare my budget status with last month's spending"

Turn 1 (PARALLEL):
  - get_budgets(month=12, year=2025)
  - get_transactions(start_date="2025-11-01", end_date="2025-11-30")
  Executed simultaneously

Turn 2 (ANALYSIS):
  AI receives both results and responds with comparative analysis
```

## Performance Benefits

| Pattern | Turns | Latency | Use Case |
|---------|-------|---------|----------|
| Sequential only | 4+ turns | High | Simple cases |
| Parallel | 2 turns | Medium | Independent calls |
| Compositional | 2-10 turns | Medium | Dependent calls |
| Hybrid (Parallel + Compositional) | 2-3 turns | Low | Complex workflows |

## System Prompt Guidelines

The AI is instructed to:

1. **Identify independent functions** → Use parallel calling
2. **Identify dependent functions** → Use compositional calling
3. **Combine when possible** → Start with parallel, then sequential
4. **Analyze results** → Use function responses to determine next steps
5. **Never fake function calls** → Always call actual functions

## Testing

Test compositional calling:
```bash
php artisan ai:test "if cash balance is over 1 million, show me last week's transactions"
```

Test parallel calling:
```bash
php artisan ai:test "bought coffee 5k" --timestamp="2025-12-06T10:00:00+07:00"
```

Test hybrid pattern:
```bash
php artisan ai:test "show my budget and spending for food category"
```

## Monitoring

Check logs for function calling patterns:
```bash
tail -f storage/logs/laravel.log | grep "tool execution"
```

Look for:
- `"AI requested parallel tool execution"` → Parallel calling detected
- `"AI requested tool execution"` → Single/compositional calling
- Multiple sequential tool calls → Compositional chaining in action

## Conclusion

Both patterns are **natively supported by Gemini** and fully implemented in WAFinance:

- ✅ Parallel Function Calling - for efficiency
- ✅ Compositional Function Calling - for complex workflows  
- ✅ Hybrid approach - for maximum performance

The orchestrator automatically handles both patterns based on how the AI structures its function calls, with no additional configuration needed.
