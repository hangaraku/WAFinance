<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\AIService;

class AIController extends Controller
{
    protected $aiService;

    public function __construct(AIService $aiService)
    {
        $this->aiService = $aiService;
    }

    public function chat()
    {
        return view('ai.chat');
    }

    public function processMessage(Request $request)
    {
        $user = Auth::user();
        $message = $request->input('message');
        $context = $request->input('context', []);
        
        // Use AI service to process the message
        $response = $this->aiService->processMessage($user, $message, $context);
        
        return response()->json($response);
    }

}