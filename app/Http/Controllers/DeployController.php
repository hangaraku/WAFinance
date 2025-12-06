<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;

class DeployController extends Controller
{
    public function pull()
    {
        try {
            // Path to the pull script
            $scriptPath = '/home/hgalih-baikfinansial/pull.sh';
            
            // Check if script exists
            if (!file_exists($scriptPath)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Script not found at: ' . $scriptPath,
                    'output' => '',
                    'timestamp' => now()->format('Y-m-d H:i:s')
                ], 404);
            }
            
            // Execute the script
            $result = Process::run("bash {$scriptPath}");
            
            // Get output and error
            $output = $result->output();
            $error = $result->errorOutput();
            $exitCode = $result->exitCode();
            
            // Log the deployment attempt
            Log::info('Deployment script executed', [
                'script' => $scriptPath,
                'exit_code' => $exitCode,
                'output' => $output,
                'error' => $error
            ]);
            
            // Determine success
            $success = $exitCode === 0;
            
            return response()->json([
                'success' => $success,
                'exit_code' => $exitCode,
                'output' => $output,
                'error' => $error,
                'timestamp' => now()->format('Y-m-d H:i:s'),
                'script_path' => $scriptPath
            ]);
            
        } catch (\Exception $e) {
            Log::error('Deployment script failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Exception: ' . $e->getMessage(),
                'output' => '',
                'timestamp' => now()->format('Y-m-d H:i:s')
            ], 500);
        }
    }
    
    public function showPullPage()
    {
        return view('deploy.pull');
    }
}

