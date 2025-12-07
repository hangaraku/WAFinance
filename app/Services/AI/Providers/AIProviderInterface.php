<?php

namespace App\Services\AI\Providers;

interface AIProviderInterface
{
    /**
     * Call the AI model.
     *
     * @param array $messages Conversation history (role, content).
     * @param array $functions List of function descriptors (optional).
     * @param string|array $functionCall 'auto', 'none', or specific function.
     * @param string|null $systemInstruction System instruction for the AI (optional).
     * @return array Normalized response structure.
     */
    public function callChat(array $messages, array $functions = [], $functionCall = 'auto', ?string $systemInstruction = null): array;
}
