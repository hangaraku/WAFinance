<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Account;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller
{
    /**
     * Display a listing of the accounts.
     */
    public function index()
    {
        $accounts = Auth::user()->activeAccounts()
            ->withCount(['transactions as total_transactions'])
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get();
            
        // Calculate total balance across all accounts
        $totalBalance = $accounts->sum('balance');
        
        return view('accounts.index', compact('accounts', 'totalBalance'));
    }

    /**
     * Show the form for creating a new account.
     */
    public function create()
    {
        return view('accounts.create');
    }

    /**
     * Store a newly created account in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:cash,bank,credit_card,investment,wallet',
            'balance' => 'required|numeric|min:0',
            'color' => 'nullable|string|max:50',
            'icon' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:1000',
        ]);

        $account = Auth::user()->accounts()->create([
            'name' => $request->name,
            'type' => $request->type,
            'balance' => $request->balance,
            'color' => $request->color,
            'icon' => $request->icon,
            'description' => $request->description,
            'is_default' => false, // New accounts are not default by default
        ]);

        return redirect()->route('accounts.index')
            ->with('success', 'Account created successfully.');
    }

    /**
     * Display the specified account.
     */
    public function show(Account $account)
    {
        // Ensure user can only see their own accounts
        if ($account->user_id !== Auth::id()) {
            abort(403);
        }

        $account->load(['transactions' => function ($query) {
            $query->orderBy('transaction_date', 'desc')
                  ->orderBy('transaction_time', 'desc')
                  ->take(20);
        }, 'transactions.category']);

        return view('accounts.show', compact('account'));
    }

    /**
     * Show the form for editing the specified account.
     */
    public function edit(Account $account)
    {
        // Ensure user can only edit their own accounts
        if ($account->user_id !== Auth::id()) {
            abort(403);
        }

        return view('accounts.edit', compact('account'));
    }

    /**
     * Update the specified account in storage.
     */
    public function update(Request $request, Account $account)
    {
        // Ensure user can only update their own accounts
        if ($account->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:cash,bank,credit_card,investment,wallet',
            'balance' => 'required|numeric',
            'color' => 'nullable|string|max:50',
            'icon' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:1000',
        ]);

        $account->update($request->only([
            'name', 'type', 'balance', 'color', 'icon', 'description'
        ]));

        return redirect()->route('accounts.index')
            ->with('success', 'Account updated successfully.');
    }

    /**
     * Remove the specified account from storage.
     */
    public function destroy(Account $account)
    {
        // Ensure user can only delete their own accounts
        if ($account->user_id !== Auth::id()) {
            abort(403);
        }

        // Check if account has transactions
        if ($account->transactions()->exists()) {
            return redirect()->route('accounts.index')
                ->with('error', 'Cannot delete account with existing transactions.');
        }

        $account->delete();

        return redirect()->route('accounts.index')
            ->with('success', 'Account deleted successfully.');
    }

    /**
     * Set an account as default.
     */
    public function setDefault(Account $account)
    {
        // Ensure user can only modify their own accounts
        if ($account->user_id !== Auth::id()) {
            abort(403);
        }

        // Remove default from all other accounts
        Auth::user()->accounts()->update(['is_default' => false]);

        // Set this account as default
        $account->update(['is_default' => true]);

        return redirect()->route('accounts.index')
            ->with('success', 'Default account updated successfully.');
    }

    /**
     * Toggle account active status.
     */
    public function toggleActive(Account $account)
    {
        // Ensure user can only modify their own accounts
        if ($account->user_id !== Auth::id()) {
            abort(403);
        }

        $account->update(['is_active' => !$account->is_active]);

        $status = $account->is_active ? 'activated' : 'deactivated';
        return redirect()->route('accounts.index')
            ->with('success', "Account {$status} successfully.");
    }

    /**
     * Get account balance summary for dashboard.
     */
    public function getBalanceSummary()
    {
        $accounts = Auth::user()->activeAccounts()
            ->select('id', 'name', 'type', 'balance', 'color', 'icon')
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get();

        return response()->json([
            'accounts' => $accounts,
            'total_balance' => $accounts->sum('balance'),
            'account_count' => $accounts->count()
        ]);
    }
}
