<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories for the authenticated user.
     */
    public function index()
    {
        $user = Auth::user();
        $categories = Category::where('user_id', $user->id)
            ->orderBy('type')
            ->orderBy('name')
            ->get()
            ->groupBy('type');

        return view('categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new category.
     */
    public function create()
    {
        return view('categories.create');
    }

    /**
     * Store a newly created category.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        // Check if this is an AJAX request
        $isAjax = $request->expectsJson() || $request->ajax();

        try {
            $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('categories')->where(function ($query) use ($user) {
                        return $query->where('user_id', $user->id);
                    })
                ],
                'type' => 'required|in:expense,income',
                'icon' => 'required|string|max:255',
                'color' => 'required|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            ], [
                'name.unique' => 'Kategori dengan nama ini sudah ada.',
                'color.regex' => 'Format warna harus berupa hex color (contoh: #FF0000).',
            ]);

            $category = Category::create([
                'user_id' => $user->id,
                'name' => $request->name,
                'type' => $request->type,
                'icon' => $request->icon,
                'color' => $request->color,
                'is_default' => false,
            ]);

            // Return JSON response for AJAX requests
            if ($isAjax) {
                return response()->json([
                    'success' => true,
                    'message' => 'Kategori berhasil dibuat!',
                    'category' => $category
                ]);
            }

            return redirect()->route('categories.index')
                ->with('success', 'Kategori berhasil dibuat!');

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Handle validation errors for AJAX requests
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $e->errors()
                ], 422);
            }
            
            // Re-throw for non-AJAX requests to let Laravel handle normally
            throw $e;
        }
    }

    /**
     * Show the form for editing the specified category.
     */
    public function edit(Category $category)
    {
        // Ensure user can only edit their own categories
        if ($category->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to category.');
        }

        return view('categories.edit', compact('category'));
    }

    /**
     * Update the specified category.
     */
    public function update(Request $request, Category $category)
    {
        // Ensure user can only update their own categories
        if ($category->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to category.');
        }

        $user = Auth::user();

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories')->where(function ($query) use ($user) {
                    return $query->where('user_id', $user->id);
                })->ignore($category->id)
            ],
            'type' => 'required|in:expense,income',
            'icon' => 'required|string|max:255',
            'color' => 'required|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
        ], [
            'name.unique' => 'Kategori dengan nama ini sudah ada.',
            'color.regex' => 'Format warna harus berupa hex color (contoh: #FF0000).',
        ]);

        $category->update([
            'name' => $request->name,
            'type' => $request->type,
            'icon' => $request->icon,
            'color' => $request->color,
        ]);

        return redirect()->route('categories.index')
            ->with('success', 'Kategori berhasil diperbarui!');
    }

    /**
     * Remove the specified category.
     */
    public function destroy(Category $category)
    {
        // Ensure user can only delete their own categories
        if ($category->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to category.');
        }

        // Check if category is being used in transactions or budgets
        $transactionCount = $category->transactions()->count();
        $budgetCount = $category->budgets()->count();

        if ($transactionCount > 0 || $budgetCount > 0) {
            return redirect()->route('categories.index')
                ->with('error', 'Kategori tidak dapat dihapus karena masih digunakan dalam transaksi atau anggaran.');
        }

        $category->delete();

        return redirect()->route('categories.index')
            ->with('success', 'Kategori berhasil dihapus!');
    }

    /**
     * Get categories for AJAX requests (for transaction form, etc.)
     */
    public function getCategories(Request $request)
    {
        $user = Auth::user();
        $type = $request->get('type', 'expense');

        $categories = Category::where('user_id', $user->id)
            ->where('type', $type)
            ->orderBy('name')
            ->get();

        return response()->json($categories);
    }
}
