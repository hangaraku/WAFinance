@extends('layouts.app')

@section('title', 'Deploy - Pull Latest Code')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b">
        <div class="px-4 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">Deploy</h1>
                    <p class="text-sm text-gray-600">Pull latest code from repository</p>
                </div>
                <button 
                    onclick="executePull()" 
                    class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center gap-2"
                    id="pullButton"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Pull Latest Code
                </button>
            </div>
        </div>
    </div>

    <!-- Content -->
    <div class="px-4 py-6">
        <!-- Status Card -->
        <div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-3 h-3 rounded-full bg-gray-400" id="statusIndicator"></div>
                <h2 class="text-lg font-medium text-gray-900">Deployment Status</h2>
            </div>
            
            <div id="statusMessage" class="text-gray-600">
                Click "Pull Latest Code" to execute the deployment script.
            </div>
            
            <div id="timestamp" class="text-sm text-gray-500 mt-2"></div>
        </div>

        <!-- Output Card -->
        <div class="bg-white rounded-lg shadow-sm border p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Script Output</h3>
            
            <div id="outputContainer" class="hidden">
                <div class="bg-gray-900 text-green-400 p-4 rounded-lg font-mono text-sm overflow-x-auto">
                    <div id="outputContent"></div>
                </div>
            </div>
            
            <div id="errorContainer" class="hidden">
                <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-lg">
                    <div id="errorContent"></div>
                </div>
            </div>
            
            <div id="emptyState" class="text-center py-8 text-gray-500">
                <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <p>No output yet. Click the pull button to execute the script.</p>
            </div>
        </div>
    </div>
</div>

<script>
async function executePull() {
    const button = document.getElementById('pullButton');
    const statusIndicator = document.getElementById('statusIndicator');
    const statusMessage = document.getElementById('statusMessage');
    const timestamp = document.getElementById('timestamp');
    const outputContainer = document.getElementById('outputContainer');
    const errorContainer = document.getElementById('errorContainer');
    const emptyState = document.getElementById('emptyState');
    const outputContent = document.getElementById('outputContent');
    const errorContent = document.getElementById('errorContent');
    
    // Reset UI
    button.disabled = true;
    button.innerHTML = `
        <svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
        </svg>
        Pulling...
    `;
    
    statusIndicator.className = 'w-3 h-3 rounded-full bg-yellow-400 animate-pulse';
    statusMessage.textContent = 'Executing deployment script...';
    timestamp.textContent = '';
    
    // Hide previous outputs
    outputContainer.classList.add('hidden');
    errorContainer.classList.add('hidden');
    emptyState.classList.add('hidden');
    
    try {
        const response = await fetch('/deploy/pull', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        
        const data = await response.json();
        
        // Update timestamp
        timestamp.textContent = `Executed at: ${data.timestamp}`;
        
        if (data.success) {
            // Success
            statusIndicator.className = 'w-3 h-3 rounded-full bg-green-500';
            statusMessage.textContent = 'Deployment completed successfully!';
            statusMessage.className = 'text-green-600';
            
            // Show output
            if (data.output) {
                outputContent.textContent = data.output;
                outputContainer.classList.remove('hidden');
            }
        } else {
            // Error
            statusIndicator.className = 'w-3 h-3 rounded-full bg-red-500';
            statusMessage.textContent = 'Deployment failed!';
            statusMessage.className = 'text-red-600';
            
            // Show error
            if (data.error) {
                errorContent.textContent = data.error;
                errorContainer.classList.remove('hidden');
            }
            
            // Show output if available
            if (data.output) {
                outputContent.textContent = data.output;
                outputContainer.classList.remove('hidden');
            }
        }
        
    } catch (error) {
        // Network error
        statusIndicator.className = 'w-3 h-3 rounded-full bg-red-500';
        statusMessage.textContent = 'Network error occurred!';
        statusMessage.className = 'text-red-600';
        
        errorContent.textContent = `Network Error: ${error.message}`;
        errorContainer.classList.remove('hidden');
    } finally {
        // Reset button
        button.disabled = false;
        button.innerHTML = `
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            Pull Latest Code
        `;
    }
}
</script>
@endsection

