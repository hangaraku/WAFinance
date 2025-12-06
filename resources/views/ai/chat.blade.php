@extends('layouts.app')

@section('content')
<div class="fixed inset-0 top-12 bottom-16 bg-gray-50 flex flex-col">
    <!-- Header -->
    <div class="bg-white border-b border-gray-200">
        <div class="px-4 py-3">
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 bg-gradient-to-r from-purple-500 to-blue-500 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-lg font-semibold text-gray-900">{{ __('ai.title') }}</h1>
                    <p class="text-xs text-gray-500">{{ __('ai.subtitle') }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Chat Container -->
    <div class="flex flex-col flex-1 min-h-0">
        <!-- Messages Area -->
        <div id="chat-messages" class="flex-1 overflow-y-auto px-4 py-4 space-y-4">
            <!-- Welcome Message -->
            <div class="flex items-start space-x-3">
                <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                    </svg>
                </div>
                <div class="flex-1">
                    <div class="bg-gray-100 rounded-2xl rounded-tl-sm p-4">
                        <p class="text-sm text-gray-900">{{ __('ai.welcome_message') }}</p>
                        <div class="mt-3 space-y-2">
                            <p class="text-xs text-gray-600 font-medium">{{ __('ai.suggestions_title') }}:</p>
                            <div class="flex flex-wrap gap-2">
                                <button onclick="sendSuggestion('{{ __('ai.suggestion_1') }}')" class="text-xs bg-white hover:bg-gray-50 text-gray-700 px-3 py-1.5 rounded-full transition-colors border border-gray-200">
                                    {{ __('ai.suggestion_1') }}
                                </button>
                                <button onclick="sendSuggestion('{{ __('ai.suggestion_2') }}')" class="text-xs bg-white hover:bg-gray-50 text-gray-700 px-3 py-1.5 rounded-full transition-colors border border-gray-200">
                                    {{ __('ai.suggestion_2') }}
                                </button>
                                <button onclick="sendSuggestion('{{ __('ai.suggestion_3') }}')" class="text-xs bg-white hover:bg-gray-50 text-gray-700 px-3 py-1.5 rounded-full transition-colors border border-gray-200">
                                    {{ __('ai.suggestion_3') }}
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="text-xs text-gray-500 mt-1 ml-4">{{ __('ai.just_now') }}</div>
                </div>
            </div>
        </div>

        <!-- Typing Indicator -->
        <div id="typing-indicator" class="px-4 py-2 hidden">
            <div class="flex items-start space-x-3">
                <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                    </svg>
                </div>
                <div class="bg-gray-100 rounded-2xl rounded-tl-sm p-4">
                    <div class="flex space-x-1">
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Input Area -->
        <div class="bg-white border-t border-gray-200 px-4 py-3 pb-4 flex-shrink-0">
            <form id="chat-form" class="flex items-end space-x-3">
                @csrf
                <div class="flex-1 relative">
                    <textarea id="message-input" 
                              rows="1" 
                              placeholder="{{ __('ai.type_message') }}"
                              class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-2xl focus:ring-2 focus:ring-purple-500 focus:border-transparent resize-none max-h-32"
                              style="min-height: 48px;"></textarea>
                    <button type="button" id="voice-button" 
                            class="absolute right-3 top-1/2 transform -translate-y-1/2 p-1 text-gray-400 hover:text-purple-500 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path>
                        </svg>
                    </button>
                </div>
                <button type="submit" 
                        class="bg-gray-600 text-white p-3 rounded-2xl hover:bg-gray-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex-shrink-0 h-12 w-12 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Action Modal -->
<div id="action-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6">
            <div class="flex items-center space-x-3 mb-4">
                <div class="w-10 h-10 bg-gradient-to-r from-purple-500 to-blue-500 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">{{ __('ai.action_available') }}</h3>
                    <p class="text-sm text-gray-500">{{ __('ai.action_description') }}</p>
                </div>
            </div>
            <div id="action-content" class="mb-6">
                <!-- Action content will be populated here -->
            </div>
            <div class="flex space-x-3">
                <button id="execute-action" class="flex-1 bg-gradient-to-r from-purple-500 to-blue-500 text-white py-2 px-4 rounded-lg hover:from-purple-600 hover:to-blue-600 transition-colors">
                    {{ __('ai.execute') }}
                </button>
                <button id="cancel-action" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                    {{ __('ai.cancel') }}
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let isTyping = false;

// Auto-resize textarea
const messageInput = document.getElementById('message-input');
messageInput.addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 128) + 'px';
});

// Send message
document.getElementById('chat-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const message = messageInput.value.trim();
    if (message) {
        sendMessage(message);
        messageInput.value = '';
        messageInput.style.height = 'auto';
    }
});

// Send suggestion
function sendSuggestion(suggestion) {
    sendMessage(suggestion);
}

// Send message function
function sendMessage(message) {
    // Add user message
    addMessage(message, 'user');
    
    // Show typing indicator
    showTypingIndicator();
    
    // Send to AI
    fetch('/api/ai/chat', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ 
            message: message,
            user_id: {{ Auth::id() }},
            platform: 'web'
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        hideTypingIndicator();
        if (data.action) {
            addMessage(data.response, 'ai', data.action);
        } else {
            addMessage(data.response, 'ai');
        }
    })
    .catch(error => {
        hideTypingIndicator();
        addMessage('{{ __("ai.error_message") }}', 'ai');
        console.error('Error:', error);
        console.error('Response status:', error.message);
    });
}

// Add message to chat
function addMessage(message, sender, action = null) {
    const messagesContainer = document.getElementById('chat-messages');
    const messageDiv = document.createElement('div');
    messageDiv.className = `flex items-start space-x-3 ${sender === 'user' ? 'flex-row-reverse space-x-reverse' : ''}`;
    
    const time = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    
    if (sender === 'user') {
        messageDiv.innerHTML = `
            <div class="flex-1 max-w-xs ml-auto">
                <div class="bg-blue-500 text-white rounded-2xl rounded-tr-sm p-4 ml-auto">
                    <p class="text-sm">${message}</p>
                </div>
                <div class="text-xs text-gray-500 mt-1 text-right">${time}</div>
            </div>
        `;
    } else {
        messageDiv.innerHTML = `
            <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                </svg>
            </div>
            <div class="flex-1">
                <div class="bg-gray-100 rounded-2xl rounded-tl-sm p-4">
                    <p class="text-sm text-gray-900">${message}</p>
                    ${action ? `<div class="mt-3">
                        <button onclick="showActionModal('${action.type}', '${action.data}')" class="text-xs bg-blue-500 text-white px-3 py-1.5 rounded-full hover:bg-blue-600 transition-colors">
                            ${action.label}
                        </button>
                    </div>` : ''}
                </div>
                <div class="text-xs text-gray-500 mt-1 ml-4">${time}</div>
            </div>
        `;
    }
    
    messagesContainer.appendChild(messageDiv);
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

// Show typing indicator
function showTypingIndicator() {
    document.getElementById('typing-indicator').classList.remove('hidden');
    const messagesContainer = document.getElementById('chat-messages');
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

// Hide typing indicator
function hideTypingIndicator() {
    document.getElementById('typing-indicator').classList.add('hidden');
}

// Show action modal
function showActionModal(type, data) {
    const modal = document.getElementById('action-modal');
    const content = document.getElementById('action-content');
    
    // Populate action content based on type
    if (type === 'add_transaction') {
        content.innerHTML = `
            <div class="bg-gray-50 rounded-lg p-4">
                <h4 class="font-medium text-gray-900 mb-2">{{ __('ai.add_transaction') }}</h4>
                <p class="text-sm text-gray-600">${data.description}</p>
                <div class="mt-2 text-sm">
                    <span class="text-gray-500">{{ __('ai.amount') }}:</span>
                    <span class="font-medium">Rp ${parseInt(data.amount).toLocaleString()}</span>
                </div>
            </div>
        `;
    }
    
    modal.classList.remove('hidden');
}

// Execute action
document.getElementById('execute-action').addEventListener('click', function() {
    // Here you would implement the actual action execution
    // For now, just close the modal
    document.getElementById('action-modal').classList.add('hidden');
    addMessage('{{ __("ai.action_executed") }}', 'ai');
});

// Cancel action
document.getElementById('cancel-action').addEventListener('click', function() {
    document.getElementById('action-modal').classList.add('hidden');
});

// Close modal when clicking outside
document.getElementById('action-modal').addEventListener('click', function(e) {
    if (e.target === this) {
        this.classList.add('hidden');
    }
});
</script>
@endsection
