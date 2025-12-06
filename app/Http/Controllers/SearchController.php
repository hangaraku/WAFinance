<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Transaction;
use App\Models\Account;
use App\Models\Budget;
use App\Models\Category;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $user = Auth::user();
        $query = $request->get('q', '');
        
        if (empty($query)) {
            return response()->json([
                'transactions' => [],
                'accounts' => [],
                'budgets' => [],
                'navigation' => []
            ]);
        }
        
        $results = [
            'transactions' => $this->searchTransactions($user->id, $query),
            'accounts' => $this->searchAccounts($user->id, $query),
            'budgets' => $this->searchBudgets($user->id, $query),
            'navigation' => $this->searchNavigation($query)
        ];
        
        return response()->json($results);
    }
    
    private function searchTransactions($userId, $query)
    {
        return Transaction::where('user_id', $userId)
            ->where(function($q) use ($query) {
                $q->where('description', 'like', "%{$query}%")
                  ->orWhere('notes', 'like', "%{$query}%")
                  ->orWhereHas('category', function($catQuery) use ($query) {
                      $catQuery->where('name', 'like', "%{$query}%");
                  })
                  ->orWhereHas('account', function($accQuery) use ($query) {
                      $accQuery->where('name', 'like', "%{$query}%");
                  });
            })
            ->with(['category', 'account'])
            ->orderBy('transaction_date', 'desc')
            ->limit(10)
            ->get()
            ->map(function($transaction) {
                return [
                    'id' => $transaction->id,
                    'type' => $transaction->type,
                    'description' => $transaction->description,
                    'amount' => $transaction->amount,
                    'date' => $transaction->transaction_date->format('d M Y'),
                    'category' => $transaction->category ? $transaction->category->name : null,
                    'account' => $transaction->account ? $transaction->account->name : null,
                    'url' => route('transactions.edit', $transaction)
                ];
            });
    }
    
    private function searchAccounts($userId, $query)
    {
        return Account::where('user_id', $userId)
            ->where('is_active', true)
            ->where(function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('type', 'like', "%{$query}%");
            })
            ->orderBy('name')
            ->limit(5)
            ->get()
            ->map(function($account) {
                return [
                    'id' => $account->id,
                    'name' => $account->name,
                    'type' => $account->type_display_name,
                    'balance' => $account->balance,
                    'url' => route('accounts.show', $account)
                ];
            });
    }
    
    private function searchBudgets($userId, $query)
    {
        return Budget::where('user_id', $userId)
            ->where(function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhereHas('category', function($catQuery) use ($query) {
                      $catQuery->where('name', 'like', "%{$query}%");
                  });
            })
            ->with('category')
            ->orderBy('name')
            ->limit(5)
            ->get()
            ->map(function($budget) {
                return [
                    'id' => $budget->id,
                    'name' => $budget->name,
                    'amount' => $budget->amount,
                    'spent' => $budget->spent,
                    'category' => $budget->category ? $budget->category->name : null,
                    'url' => route('budgets.edit', $budget)
                ];
            });
    }
    
    private function searchNavigation($query)
    {
        $navigationItems = [
            [
                'name' => __('common.transactions'),
                'url' => route('transactions'),
                'icon' => 'M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'
            ],
            [
                'name' => __('common.stats'),
                'url' => route('stats'),
                'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'
            ],
            [
                'name' => __('common.accounts'),
                'url' => route('accounts.index'),
                'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'
            ],
            [
                'name' => __('common.more'),
                'url' => route('settings.index'),
                'icon' => 'M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z'
            ]
        ];
        
        return collect($navigationItems)
            ->filter(function($item) use ($query) {
                return stripos($item['name'], $query) !== false;
            })
            ->values();
    }
}
