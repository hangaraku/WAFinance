# Function Calling Patterns - Visual Guide

## 1. Sequential (Old/Inefficient Way)

```
User: "bought coffee 5k"

Turn 1:  User → AI
         AI → get_categories()
         
Turn 2:  get_categories() → AI
         AI → get_accounts()
         
Turn 3:  get_accounts() → AI
         AI → add_transaction()
         
Turn 4:  add_transaction() → AI
         AI → User (confirmation)

Total: 4 turns, 3 function calls
```

## 2. Parallel Function Calling (Efficient)

```
User: "bought coffee 5k"

Turn 1:  User → AI
         AI → ┬→ get_categories() ─┐
              └→ get_accounts() ────┤
                                    │
Turn 2:  ←──────────────────────────┘
         AI → add_transaction()
         
Turn 3:  add_transaction() → AI
         AI → User (confirmation)

Total: 3 turns, 3 function calls
Improvement: 25% faster (4→3 turns)
```

## 3. Compositional Function Calling (For Dependencies)

```
User: "show cash transactions last week"

Turn 1:  User → AI
         AI → get_accounts()
         
Turn 2:  get_accounts() → AI
         AI analyzes: "Cash = ID 15"
         AI → get_transactions(account_id=15)
         
Turn 3:  get_transactions() → AI
         AI → User (formatted results)

Total: 3 turns, 2 function calls (chained)
```

## 4. Conditional Compositional (Advanced)

```
User: "if my food spending > 500k, create budget for next month at 600k"

Turn 1:  User → AI
         AI → get_transactions(category_id=17)
         
Turn 2:  get_transactions() → AI
         AI analyzes: "Total = 567k > 500k"
         AI → add_budget(amount=600000)
         
Turn 3:  add_budget() → AI
         AI → User (confirmation)

Total: 3 turns, 2 function calls (conditional chain)
```

## 5. Hybrid Pattern (Parallel + Compositional)

```
User: "bought lunch 25k and coffee 5k"

Turn 1:  User → AI
         AI → ┬→ get_categories() ─┐
              └→ get_accounts() ────┤
                                    │
Turn 2:  ←──────────────────────────┘
         AI analyzes both results
         AI → add_multiple_transactions()
         
Turn 3:  add_multiple_transactions() → AI
         AI → User (confirmation)

Total: 3 turns, 3 function calls
Optimization: Parallel start + compositional finish
```

## 6. Complex Hybrid (Maximum Efficiency)

```
User: "compare my budget with spending for food and transport"

Turn 1:  User → AI
         AI → ┬→ get_budgets() ─────────┐
              ├→ get_transactions(food) ─┤
              └→ get_transactions(trans)─┤
                                         │
Turn 2:  ←───────────────────────────────┘
         AI analyzes all 3 results
         AI → User (detailed comparison)

Total: 2 turns, 3 function calls
Maximum efficiency: 50% faster than sequential
```

## Pattern Decision Tree

```
┌─────────────────────────────────────┐
│  Need to call functions?            │
└────────────┬────────────────────────┘
             │
    ┌────────┴──────────┐
    │  Independent?     │
    │  (no dependencies)│
    └────────┬──────────┘
             │
      ┌──────┴──────┐
      │             │
     YES           NO
      │             │
      ▼             ▼
┌──────────┐  ┌──────────────┐
│ PARALLEL │  │ COMPOSITIONAL│
│ CALLING  │  │ CALLING      │
└──────────┘  └──────────────┘
      │             │
      └─────┬───────┘
            │
     ┌──────┴────────┐
     │  More calls?  │
     └──────┬────────┘
            │
      ┌─────┴─────┐
      │           │
     YES         NO
      │           │
      ▼           ▼
 ┌────────┐  ┌────────┐
 │ HYBRID │  │  DONE  │
 └────────┘  └────────┘
```

## Performance Comparison

| Scenario | Sequential | Parallel | Compositional | Hybrid |
|----------|-----------|----------|---------------|--------|
| Transaction creation | 4 turns | 3 turns | N/A | 3 turns |
| Account lookup + query | 3 turns | N/A | 3 turns | 2 turns |
| Multiple independent calls | 5 turns | 2 turns | N/A | 2 turns |
| Conditional workflow | 4 turns | N/A | 3 turns | 3 turns |

## Key Takeaways

✅ **Use Parallel** when functions are independent
✅ **Use Compositional** when functions have dependencies  
✅ **Use Hybrid** for complex workflows
✅ **Up to 60% reduction** in conversation turns
✅ **Native Gemini feature** - no custom implementation needed

## Implementation Status

- ✅ Parallel Function Calling - Implemented
- ✅ Compositional Function Calling - Implemented  
- ✅ Hybrid Pattern - Implemented
- ✅ System Prompt Updated - Done
- ✅ Test Suite - Available

Run tests:
```bash
php artisan ai:test-compositional
```
