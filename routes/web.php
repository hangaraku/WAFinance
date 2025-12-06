<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TransactionController;

// Public routes
Route::get('/', function () {
    return view('welcome');
})->name('welcome');

// Authentication routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Language switching route
Route::get('/language/{locale}', function ($locale) {
    if (in_array($locale, ['en', 'id'])) {
        session(['locale' => $locale]);
    }
    return redirect()->back();
})->name('language.switch');

// Protected routes
Route::middleware('auth')->group(function () {
    Route::get('/stats', [TransactionController::class, 'stats'])->name('stats');
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions');
    Route::get('/transaction/new', [TransactionController::class, 'create'])->name('transaction.new');
    Route::post('/transactions', [TransactionController::class, 'store'])->name('transaction.store');
    Route::get('/transactions/{transaction}/edit', [TransactionController::class, 'edit'])->name('transactions.edit');
    Route::put('/transactions/{transaction}', [TransactionController::class, 'update'])->name('transactions.update');
    Route::delete('/transactions/{transaction}', [TransactionController::class, 'destroy'])->name('transactions.destroy');
    
    // Budget management routes
    Route::get('/budgets', [App\Http\Controllers\BudgetController::class, 'index'])->name('budgets.index');
    Route::get('/budgets/create', [App\Http\Controllers\BudgetController::class, 'create'])->name('budgets.create');
    Route::post('/budgets', [App\Http\Controllers\BudgetController::class, 'store'])->name('budgets.store');
    Route::get('/budgets/{budget}/edit', [App\Http\Controllers\BudgetController::class, 'edit'])->name('budgets.edit');
    Route::put('/budgets/{budget}', [App\Http\Controllers\BudgetController::class, 'update'])->name('budgets.update');
    Route::delete('/budgets/{budget}', [App\Http\Controllers\BudgetController::class, 'destroy'])->name('budgets.destroy');
    
    // Goal management routes
    Route::get('/goals', [App\Http\Controllers\GoalController::class, 'index'])->name('goals.index');
    Route::get('/goals/create', [App\Http\Controllers\GoalController::class, 'create'])->name('goals.create');
    Route::post('/goals', [App\Http\Controllers\GoalController::class, 'store'])->name('goals.store');
    Route::get('/goals/{goal}/edit', [App\Http\Controllers\GoalController::class, 'edit'])->name('goals.edit');
    Route::put('/goals/{goal}', [App\Http\Controllers\GoalController::class, 'update'])->name('goals.update');
    Route::delete('/goals/{goal}', [App\Http\Controllers\GoalController::class, 'destroy'])->name('goals.destroy');
    Route::post('/goals/{goal}/toggle-complete', [App\Http\Controllers\GoalController::class, 'toggleComplete'])->name('goals.toggle-complete');
    Route::post('/goals/{goal}/update-progress', [App\Http\Controllers\GoalController::class, 'updateProgress'])->name('goals.update-progress');
    
    // Account management routes
    Route::get('/accounts', [App\Http\Controllers\AccountController::class, 'index'])->name('accounts.index');
    Route::get('/accounts/create', [App\Http\Controllers\AccountController::class, 'create'])->name('accounts.create');
    Route::post('/accounts', [App\Http\Controllers\AccountController::class, 'store'])->name('accounts.store');
    Route::get('/accounts/{account}', [App\Http\Controllers\AccountController::class, 'show'])->name('accounts.show');
    Route::get('/accounts/{account}/edit', [App\Http\Controllers\AccountController::class, 'edit'])->name('accounts.edit');
    Route::put('/accounts/{account}', [App\Http\Controllers\AccountController::class, 'update'])->name('accounts.update');
    Route::delete('/accounts/{account}', [App\Http\Controllers\AccountController::class, 'destroy'])->name('accounts.destroy');
    Route::post('/accounts/{account}/set-default', [App\Http\Controllers\AccountController::class, 'setDefault'])->name('accounts.set-default');
    Route::post('/accounts/{account}/toggle-active', [App\Http\Controllers\AccountController::class, 'toggleActive'])->name('accounts.toggle-active');
    
    // Category management routes
    Route::get('/categories', [App\Http\Controllers\CategoryController::class, 'index'])->name('categories.index');
    Route::get('/categories/create', [App\Http\Controllers\CategoryController::class, 'create'])->name('categories.create');
    Route::post('/categories', [App\Http\Controllers\CategoryController::class, 'store'])->name('categories.store');
    Route::get('/categories/{category}/edit', [App\Http\Controllers\CategoryController::class, 'edit'])->name('categories.edit');
    Route::put('/categories/{category}', [App\Http\Controllers\CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [App\Http\Controllers\CategoryController::class, 'destroy'])->name('categories.destroy');
    
    // Settings routes
    Route::get('/settings', [App\Http\Controllers\SettingsController::class, 'index'])->name('settings.index');
    
    // AI routes
    Route::get('/ai/chat', [App\Http\Controllers\AIController::class, 'chat'])->name('ai.chat');
});

// Deploy routes (for development)
Route::get('/deploy', [App\Http\Controllers\DeployController::class, 'showPullPage'])->name('deploy.pull');
Route::post('/deploy/pull', [App\Http\Controllers\DeployController::class, 'pull'])->name('deploy.execute');

// API routes for AJAX
Route::middleware(['auth'])->prefix('api')->group(function () {
    Route::get('/transactions/by-date', [App\Http\Controllers\TransactionController::class, 'getTransactionsByDate']);
    Route::get('/monthly-stats', [App\Http\Controllers\TransactionController::class, 'getMonthlyStats']);
    Route::get('/accounts/balance-summary', [App\Http\Controllers\AccountController::class, 'getBalanceSummary']);
    Route::get('/categories', [App\Http\Controllers\CategoryController::class, 'getCategories']);
    Route::get('/search', [App\Http\Controllers\SearchController::class, 'search']);
});

