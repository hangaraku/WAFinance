/**
 * AI Chat API Client with Auto-detected Timezone
 * 
 * Usage:
 * const aiChat = new AIChatClient(userId);
 * const response = await aiChat.sendMessage("Your message here");
 */

export class AIChatClient {
    constructor(userId, options = {}) {
        this.userId = userId;
        this.apiUrl = options.apiUrl || '/api/ai/chat';
        this.timezone = this.getClientTimezone();
    }

    /**
     * Auto-detect user's timezone from browser
     * @returns {string} IANA timezone identifier (e.g. 'Asia/Jakarta')
     */
    getClientTimezone() {
        try {
            // Get timezone from Intl API (most reliable)
            const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
            return timezone || 'UTC';
        } catch (error) {
            console.warn('Could not detect timezone:', error);
            return 'UTC';
        }
    }

    /**
     * Get current time in ISO 8601 format with timezone offset
     * @returns {string} ISO 8601 string (e.g. '2025-12-07T14:30:00+07:00')
     */
    getCurrentTimeWithOffset() {
        const now = new Date();
        return now.toISOString().replace('Z', '') + this.getTimezoneOffset();
    }

    /**
     * Get timezone offset in +HH:mm or -HH:mm format
     * @returns {string} Timezone offset (e.g. '+07:00')
     */
    getTimezoneOffset() {
        const now = new Date();
        const offset = -now.getTimezoneOffset();
        const hours = Math.floor(Math.abs(offset) / 60);
        const minutes = Math.abs(offset) % 60;
        const sign = offset >= 0 ? '+' : '-';
        return `${sign}${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
    }

    /**
     * Send a message to the AI chat API
     * @param {string} message - The user's message
     * @param {object} options - Additional options
     * @returns {Promise<object>} API response
     */
    async sendMessage(message, options = {}) {
        try {
            const payload = {
                message,
                user_id: this.userId,
                timezone: this.timezone,
                current_time: this.getCurrentTimeWithOffset(),
                platform: options.platform || 'web'
            };

            const response = await fetch(this.apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(options.headers || {})
                },
                body: JSON.stringify(payload),
                credentials: options.credentials || 'same-origin'
            });

            if (!response.ok) {
                const error = await response.json().catch(() => ({}));
                throw new Error(error.message || `API error: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error('AI Chat API Error:', error);
            throw error;
        }
    }

    /**
     * Get current timezone info for debugging
     * @returns {object} Timezone information
     */
    getTimezoneInfo() {
        return {
            timezone: this.timezone,
            currentTime: this.getCurrentTimeWithOffset(),
            offset: this.getTimezoneOffset(),
            timestamp: new Date().toISOString()
        };
    }
}

/**
 * Helper function for quick API calls
 * @param {string} message - User message
 * @param {number} userId - User ID
 * @param {object} options - Options
 * @returns {Promise<object>} API response
 */
export async function sendAIMessage(message, userId, options = {}) {
    const client = new AIChatClient(userId, options);
    return client.sendMessage(message, options);
}
