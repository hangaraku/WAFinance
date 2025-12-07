<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

class SettingsController extends Controller
{
    /**
     * Display the settings page.
     */
    public function index()
    {
        return view('settings');
    }

    /**
     * Update account details: name, optional password, and whatsapp number.
     */
    public function updateAccount(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:8'],
            'whatsapp_number' => [
                'nullable',
                'string',
                'regex:/^\+?[1-9]\d{1,14}$/',
                Rule::unique('users')->ignore($user->id),
            ],
        ]);

        $update = ['name' => $validated['name']];

        if (!empty($validated['password'])) {
            $update['password'] = Hash::make($validated['password']);
        }

        if (!empty($validated['whatsapp_number'])) {
            $update['whatsapp_number'] = $this->normalizePhoneNumber($validated['whatsapp_number']);
        }

        $user->update($update);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'user' => $user->fresh()]);
        }

        return back()->with('success', __('settings.user') . ' updated');
    }

    /**
     * Show the account settings page (Akun).
     */
    public function account()
    {
        $user = Auth::user();
        return view('settings.account', ['user' => $user]);
    }

    /**
     * Update WhatsApp number for the authenticated user.
     */
    public function updateWhatsApp(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'whatsapp_number' => [
                'required',
                'string',
                'regex:/^\+?[1-9]\d{1,14}$/', // E.164 format
                Rule::unique('users')->ignore($user->id),
            ],
        ]);

        // Normalize the phone number
        $normalized = $this->normalizePhoneNumber($validated['whatsapp_number']);

        $user->update(['whatsapp_number' => $normalized]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'WhatsApp number updated successfully',
                'whatsapp_number' => $normalized,
            ]);
        }

        return back()->with('success', 'WhatsApp number updated successfully');
    }

    /**
     * Remove WhatsApp number for the authenticated user.
     */
    public function removeWhatsApp(Request $request)
    {
        $user = Auth::user();
        $user->update(['whatsapp_number' => null]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'WhatsApp number removed successfully',
            ]);
        }

        return back()->with('success', 'WhatsApp number removed successfully');
    }

    /**
     * Delete the authenticated user's account.
     */
    public function deleteAccount(Request $request)
    {
        $user = Auth::user();
        
        // Log out the user first
        Auth::logout();
        
        // Delete the user account
        $user->delete();
        
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Account deleted successfully',
            ]);
        }

        return redirect('/')->with('success', 'Account deleted successfully');
    }

    /**
     * Normalize phone number to standard format: 62xxxxxxxxxx (no + prefix)
     * Handles:
     *   +6281357737545 => 6281357737545
     *   081357737545 => 6281357737545
     *   6281357737545 => 6281357737545
     */
    protected function normalizePhoneNumber(string $phone): string
    {
        // Remove all non-numeric characters
        $normalized = preg_replace('/[^\d]/', '', $phone);
        
        // Handle leading 0 (Indonesian format: 081xxx => 628xxx)
        if (str_starts_with($normalized, '0')) {
            $normalized = '62' . substr($normalized, 1);
        }
        // If doesn't start with 62, assume it's a number without country code
        elseif (!str_starts_with($normalized, '62')) {
            $normalized = '62' . $normalized;
        }
        // Already in 62xxx format, leave as is

        return $normalized;
    }
}
