<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Prompt Editor - Cashfloo Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center space-x-4">
                    <a href="{{ route('admin.dashboard') }}" class="text-gray-600 hover:text-gray-900">
                        ← Dashboard
                    </a>
                    <h1 class="text-xl font-bold text-gray-900">System Prompt Editor</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <form method="POST" action="{{ route('admin.logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="text-red-600 hover:text-red-700 font-medium">
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-6 flex items-center justify-between">
                <span>{{ session('success') }}</span>
                <button onclick="this.parentElement.remove()" class="text-green-700 hover:text-green-900">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        @endif

        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-6">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white rounded-lg shadow-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">Edit System Prompt</h2>
                        @if($lastModified)
                            <p class="text-sm text-gray-600 mt-1">
                                Last modified: {{ date('F j, Y - H:i:s', $lastModified) }}
                            </p>
                        @endif
                    </div>
                    <div class="flex space-x-2">
                        <button 
                            type="button" 
                            onclick="document.getElementById('editor-form').reset()" 
                            class="px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition"
                        >
                            Reset
                        </button>
                        <button 
                            type="button" 
                            onclick="document.getElementById('editor-form').submit()" 
                            class="px-4 py-2 text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition"
                        >
                            Save Changes
                        </button>
                    </div>
                </div>
            </div>

            <form id="editor-form" method="POST" action="{{ route('admin.system-prompt.update') }}" class="p-6">
                @csrf
                <div class="mb-4">
                    <label for="content" class="block text-sm font-medium text-gray-700 mb-2">
                        System Prompt Content
                    </label>
                    <div class="relative">
                        <textarea 
                            id="content" 
                            name="content" 
                            rows="30" 
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono text-sm"
                            placeholder="Enter system prompt..."
                            spellcheck="false"
                        >{{ old('content', $content) }}</textarea>
                        <div class="absolute bottom-2 right-2 text-xs text-gray-500">
                            <span id="char-count">{{ strlen($content) }}</span> characters
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                    <div class="text-sm text-gray-600">
                        <p><strong>Note:</strong> Changes take effect immediately. A backup is created automatically.</p>
                    </div>
                    <button 
                        type="submit" 
                        class="px-6 py-3 text-white bg-blue-600 hover:bg-blue-700 rounded-lg font-semibold transition"
                    >
                        💾 Save System Prompt
                    </button>
                </div>
            </form>
        </div>

        <!-- Help Section -->
        <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-blue-900 mb-3">Editing Tips</h3>
            <ul class="list-disc list-inside text-blue-800 space-y-2 text-sm">
                <li>Use <code class="bg-blue-100 px-2 py-1 rounded">{{TIMESTAMP}}</code>, <code class="bg-blue-100 px-2 py-1 rounded">{{DATE_STR}}</code>, and <code class="bg-blue-100 px-2 py-1 rounded">{{TIME_STR}}</code> for dynamic values</li>
                <li>Keep instructions clear and concise for better AI performance</li>
                <li>Test changes by interacting with the AI chat after saving</li>
                <li>Previous versions are automatically backed up in <code class="bg-blue-100 px-2 py-1 rounded">resources/ai/</code></li>
            </ul>
        </div>
    </div>

    <script>
        // Character counter
        const textarea = document.getElementById('content');
        const charCount = document.getElementById('char-count');
        
        textarea.addEventListener('input', function() {
            charCount.textContent = this.value.length;
        });

        // Auto-save warning
        let hasUnsavedChanges = false;
        textarea.addEventListener('input', function() {
            hasUnsavedChanges = true;
        });

        document.getElementById('editor-form').addEventListener('submit', function() {
            hasUnsavedChanges = false;
        });

        window.addEventListener('beforeunload', function(e) {
            if (hasUnsavedChanges) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    </script>
</body>
</html>
