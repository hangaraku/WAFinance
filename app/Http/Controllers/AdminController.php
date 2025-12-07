<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class AdminController extends Controller
{
    private const ADMIN_PASSWORD = 'T0s3c4k1@MP';

    /**
     * Show admin login page
     */
    public function showLogin()
    {
        if (session('admin_authenticated')) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.login');
    }

    /**
     * Handle admin login
     */
    public function login(Request $request)
    {
        $request->validate([
            'password' => 'required|string'
        ]);

        if ($request->password === self::ADMIN_PASSWORD) {
            session(['admin_authenticated' => true]);
            return redirect()->route('admin.dashboard')->with('success', 'Logged in successfully');
        }

        return back()->withErrors(['password' => 'Invalid password'])->withInput();
    }

    /**
     * Handle admin logout
     */
    public function logout()
    {
        session()->forget('admin_authenticated');
        return redirect()->route('admin.login')->with('success', 'Logged out successfully');
    }

    /**
     * Show admin dashboard
     */
    public function dashboard()
    {
        if (!session('admin_authenticated')) {
            return redirect()->route('admin.login');
        }

        return view('admin.dashboard');
    }

    /**
     * Show system prompt editor
     */
    public function systemPrompt()
    {
        if (!session('admin_authenticated')) {
            return redirect()->route('admin.login');
        }

        $promptPath = resource_path('ai/system_prompt.txt');
        $content = File::exists($promptPath) ? File::get($promptPath) : '';

        return view('admin.system-prompt', [
            'content' => $content,
            'lastModified' => File::exists($promptPath) ? File::lastModified($promptPath) : null
        ]);
    }

    /**
     * Update system prompt
     */
    public function updateSystemPrompt(Request $request)
    {
        if (!session('admin_authenticated')) {
            return redirect()->route('admin.login');
        }

        $request->validate([
            'content' => 'required|string'
        ]);

        $promptPath = resource_path('ai/system_prompt.txt');
        
        // Backup current version
        if (File::exists($promptPath)) {
            $backupPath = resource_path('ai/system_prompt_backup_' . time() . '.txt');
            File::copy($promptPath, $backupPath);
        }

        File::put($promptPath, $request->content);

        return back()->with('success', 'System prompt updated successfully');
    }
}
