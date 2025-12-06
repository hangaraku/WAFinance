<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Goal;
use App\Models\Account;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class GoalController extends Controller
{
    /**
     * Display a listing of the goals.
     */
    public function index()
    {
        $user = Auth::user();
        
        // Get all goals for the user
        $goals = Goal::where('user_id', $user->id)
            ->orderBy('target_date')
            ->get();
            
        // Get user accounts for goal tracking
        $accounts = $user->activeAccounts()
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get();
            
        // Calculate total savings across all accounts
        $totalSavings = $accounts->sum('balance');
        
        // Calculate progress for each goal
        $goals->each(function ($goal) {
            // Use current_amount field for progress calculation (same as dashboard)
            $goal->progress_percentage = $goal->target_amount > 0 ? min(100, ($goal->current_amount / $goal->target_amount) * 100) : 0;
            $goal->remaining_amount = max(0, $goal->target_amount - $goal->current_amount);
        });
        
        return view('goals.index', compact('goals', 'accounts', 'totalSavings'));
    }

    /**
     * Show the form for creating a new goal.
     */
    public function create()
    {
        $user = Auth::user();
        $accounts = $user->activeAccounts()
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get();
            
        return view('goals.create', compact('accounts'));
    }

    /**
     * Store a newly created goal in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'target_amount' => 'required|numeric|min:0',
            'current_amount' => 'nullable|numeric|min:0',
            'target_date' => 'required|date|after:today',
            'priority' => 'required|in:low,medium,high',
            'category' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ]);

        $user = Auth::user();
        
        $goal = Goal::create([
            'user_id' => $user->id,
            'name' => $request->name,
            'description' => $request->description,
            'target_amount' => $request->target_amount,
            'current_amount' => $request->current_amount ?? 0,
            'target_date' => $request->target_date,
            'priority' => $request->priority,
            'category' => $request->category,
            'notes' => $request->notes,
            'is_completed' => false,
        ]);

        return redirect()->route('goals.index')
            ->with('success', 'Tujuan keuangan berhasil dibuat!');
    }

    /**
     * Show the form for editing the specified goal.
     */
    public function edit(Goal $goal)
    {
        // Ensure user can only edit their own goals
        if ($goal->user_id !== Auth::id()) {
            abort(403);
        }

        $user = Auth::user();
        $accounts = $user->activeAccounts()
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get();
            
        return view('goals.edit', compact('goal', 'accounts'));
    }

    /**
     * Update the specified goal in storage.
     */
    public function update(Request $request, Goal $goal)
    {
        // Ensure user can only update their own goals
        if ($goal->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'target_amount' => 'required|numeric|min:0',
            'current_amount' => 'nullable|numeric|min:0',
            'target_date' => 'required|date',
            'priority' => 'required|in:low,medium,high',
            'category' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ]);

        $goal->update($request->only([
            'name', 'description', 'target_amount', 'current_amount', 
            'target_date', 'priority', 'category', 'notes'
        ]));

        return redirect()->route('goals.index')
            ->with('success', 'Tujuan keuangan berhasil diupdate!');
    }

    /**
     * Remove the specified goal from storage.
     */
    public function destroy(Goal $goal)
    {
        // Ensure user can only delete their own goals
        if ($goal->user_id !== Auth::id()) {
            abort(403);
        }

        $goal->delete();

        return redirect()->route('goals.index')
            ->with('success', 'Tujuan keuangan berhasil dihapus!');
    }

    /**
     * Toggle goal completion status.
     */
    public function toggleComplete(Goal $goal)
    {
        // Ensure user can only modify their own goals
        if ($goal->user_id !== Auth::id()) {
            abort(403);
        }

        $goal->update(['is_completed' => !$goal->is_completed]);

        $status = $goal->is_completed ? 'diselesaikan' : 'dibuka kembali';
        return redirect()->route('goals.index')
            ->with('success', "Tujuan '{$goal->name}' berhasil {$status}!");
    }

    /**
     * Update current amount for a goal.
     */
    public function updateProgress(Request $request, Goal $goal)
    {
        // Ensure user can only modify their own goals
        if ($goal->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'current_amount' => 'required|numeric|min:0|max:' . $goal->target_amount,
        ]);

        $goal->update(['current_amount' => $request->current_amount]);

        return redirect()->route('goals.index')
            ->with('success', 'Progress tujuan berhasil diupdate!');
    }
}
