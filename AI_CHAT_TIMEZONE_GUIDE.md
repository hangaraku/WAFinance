# AI Chat API - Timezone Detection Guide

## Overview

The AI Chat API now supports automatic timezone detection for accurate date calculations. The system works in two ways:

1. **Frontend sends timezone** (Recommended): The frontend detects the user's timezone using JavaScript and sends it with each request
2. **Server fallback**: If no timezone is provided, the server uses a default timezone

## API Endpoint

```
POST /api/ai/chat
```

### Request Body

```json
{
  "message": "string (required) - The user's message",
  "user_id": "integer (required) - The user's ID",
  "timezone": "string (optional) - IANA timezone (e.g. 'Asia/Jakarta')",
  "current_time": "string (optional) - ISO 8601 with offset (e.g. '2025-12-07T14:40:00+07:00')",
  "platform": "string (optional) - 'web', 'whatsapp', or 'telegram' (default: 'web')"
}
```

### Response

```json
{
  "response": "string - AI response",
  "action": null,
  "error": false,
  "model": "string - Model name",
  "usage": null,
  "timestamp": "string - Request timestamp",
  "metadata": {
    "user_id": 123,
    "platform": "web",
    "timezone": "Asia/Jakarta",
    "timestamp": "2025-12-07T14:40:00+07:00",
    "message_id": "msg_..."
  }
}
```

## Frontend Integration

### Using the JavaScript Client

```javascript
import { AIChatClient, sendAIMessage } from '/resources/js/api/ai-chat.js';

// Method 1: Using the client class
const aiChat = new AIChatClient(userId);
const response = await aiChat.sendMessage("kemarin aku makan gule 15rb");

console.log(response.response); // AI's response
console.log(response.metadata); // Includes timezone info
```

### Getting Timezone Info

```javascript
const aiChat = new AIChatClient(userId);

// Get timezone information
const tzInfo = aiChat.getTimezoneInfo();
console.log(tzInfo);
// Output:
// {
//   timezone: "Asia/Jakarta",
//   currentTime: "2025-12-07T14:40:00+07:00",
//   offset: "+07:00",
//   timestamp: "2025-12-07T07:40:00Z"
// }
```

### Manual API Call

```javascript
const payload = {
  message: "bought coffee 5000",
  user_id: 3,
  timezone: Intl.DateTimeFormat().resolvedOptions().timeZone, // Auto-detect
  current_time: new Date().toISOString().replace('Z', '') + '+07:00', // With offset
  platform: 'web'
};

const response = await fetch('/api/ai/chat', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify(payload)
});

const data = await response.json();
console.log(data.response);
```

## JavaScript Timezone Detection

### Get User's Timezone

```javascript
// Recommended: Use Intl API
const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
console.log(timezone); // e.g. "Asia/Jakarta"
```

### Get Current Time with Timezone Offset

```javascript
function getCurrentTimeWithOffset() {
  const now = new Date();
  const offset = -now.getTimezoneOffset();
  const hours = Math.floor(Math.abs(offset) / 60);
  const minutes = Math.abs(offset) % 60;
  const sign = offset >= 0 ? '+' : '-';
  const tzOffset = `${sign}${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
  
  // Format: 2025-12-07T14:40:00+07:00
  return now.toISOString().replace('Z', '') + tzOffset;
}
```

### Common Timezones

- **Indonesia**: Asia/Jakarta (GMT+7), Asia/Makassar (GMT+8), Asia/Jayapura (GMT+9)
- **Asia**: Asia/Bangkok (GMT+7), Asia/Singapore (GMT+8), Asia/Tokyo (GMT+9)
- **Europe**: Europe/London (GMT+0), Europe/Paris (GMT+1), Europe/Istanbul (GMT+3)
- **Americas**: America/New_York (GMT-5), America/Chicago (GMT-6), America/Los_Angeles (GMT-8)
- **UTC**: UTC (GMT+0)

## Examples

### Example 1: User in Jakarta adds transaction

```javascript
const aiChat = new AIChatClient(userId);

const response = await aiChat.sendMessage(
  "kemarin aku makan gule 15rb, tadi pagi makan lg habis 20rb karena sama es teh"
);

// AI will calculate:
// - "kemarin" = yesterday in Jakarta time (Sabtu, 6 Desember)
// - "tadi pagi" = this morning in Jakarta time (Minggu, 7 Desember)
```

### Example 2: User in UTC asks about last week

```javascript
const aiChat = new AIChatClient(userId);

const response = await aiChat.sendMessage(
  "show me my expenses from last week"
);

// AI will use UTC timezone for date calculations
```

### Example 3: User switches timezone

```javascript
// If user travels or changes timezone, send explicit timezone:
const response = await aiChat.sendMessage(
  "hari ini aku belanja berapa?",
  { timezone: 'America/New_York' } // Override default
);
```

## Testing

### CLI Test Commands

All CLI commands now support `--timezone` option:

```bash
# Test with Asia/Jakarta timezone
php artisan ai:test "kemarin aku makan gule 15rb" --timezone=Asia/Jakarta

# Test with UTC timezone
php artisan ai:test "kemarin aku makan gule 15rb" --timezone=UTC

# Test with UTC-9 (Alaska)
php artisan ai:test "kemarin aku makan gule 15rb" --timezone=Etc/GMT+9

# Interactive chat with specific timezone
php artisan ai:chat --user=galih@baikfinansial.com --timezone=Asia/Bangkok

# Compositional calling test
php artisan ai:test-compositional --timezone=Europe/London
```

## Implementation Checklist

- [x] Backend API accepts `timezone` and `current_time` parameters
- [x] CLI commands support `--timezone` option
- [x] Frontend JavaScript client for timezone detection
- [x] Timezone validation in request
- [x] Fallback to server timezone if not provided
- [x] Test coverage for multiple timezones
- [x] Documentation

## Security Notes

- Timezone is user-provided input and is validated
- Current time from frontend is optional (server time used if not provided)
- All timezone strings are validated against PHP's timezone database
- Invalid timezones will fall back to server default

## Future Enhancements

- Integrate MaxMind GeoIP2 for IP-based timezone detection as fallback
- Store user's preferred timezone in profile
- Auto-detect timezone on first login
- Support DST (Daylight Saving Time) transitions
