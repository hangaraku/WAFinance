# Messaging Webhook Integration Guide

## Overview

The messaging webhook system allows users to interact with the Cashfloo AI assistant through messaging platforms like WhatsApp, Telegram, SMS, etc., via n8n or other webhook providers.

## Features

- ✅ Secure webhook authentication with HMAC SHA256 signatures
- ✅ User identification via WhatsApp phone number
- ✅ Conversation history management (Redis + MySQL)
- ✅ AI-powered responses using existing AIService
- ✅ Message persistence and audit trail
- ✅ Idempotent message processing
- ✅ Support for unregistered users
- ✅ WhatsApp Business API verification endpoint

## Architecture

### Data Flow

```
n8n/WhatsApp Provider → Webhook Endpoint → MessagingService
                                               ↓
                                    ┌──────────┴──────────┐
                                    ↓                     ↓
                            Find User by Phone    Load History (Redis/MySQL)
                                    ↓                     ↓
                                    └──────────┬──────────┘
                                               ↓
                                         AIService
                                               ↓
                                    ┌──────────┴──────────┐
                                    ↓                     ↓
                            Save Messages          Update Redis Cache
                                    ↓                     ↓
                                    └──────────┬──────────┘
                                               ↓
                                        Return Response
                                               ↓
                                    n8n/WhatsApp Provider
```

### History Management Strategy

**Short-term (Redis):**
- Stores last 50 messages per user/channel
- TTL: 24 hours
- Fast retrieval for recent conversations
- Optional (falls back to MySQL if unavailable)

**Long-term (MySQL):**
- Stores all messages permanently
- Full audit trail
- Fallback when Redis is unavailable
- Query recent messages when Redis cache is cold

**AI Context:**
- Only last 20 messages sent to AI (cost optimization)
- Trim by message count (can be enhanced with token counting)
- Includes user metadata: `user_id`, `name`, `whatsapp_number`

## Setup

### 1. Database Migration

Migrations are already created. Run:

```bash
php artisan migrate
```

This creates:
- `whatsapp_number` column in `users` table
- `messages` table for conversation history

### 2. Environment Configuration

Add to `.env`:

```env
# Webhook Security
MESSAGING_WEBHOOK_SECRET=your-secure-random-secret-here
MESSAGING_VERIFY_TOKEN=your-whatsapp-verify-token

# Redis (Optional but recommended for performance)
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

Generate a secure webhook secret:

```bash
php -r "echo bin2hex(random_bytes(32)) . PHP_EOL;"
```

### 3. User Configuration

Users must set their WhatsApp number in account settings:

**Web UI:**
Navigate to Settings → WhatsApp Integration

**API Endpoint:**
```http
POST /settings/whatsapp
Content-Type: application/json

{
  "whatsapp_number": "+6281234567890"
}
```

Phone number format: E.164 (e.g., `+6281234567890`)

## Webhook Endpoints

### 1. Message Webhook (POST)

**URL:** `POST /api/messaging/webhook`

**Headers:**
```
Content-Type: application/json
X-Webhook-Signature: <hmac_sha256_signature>
```

**Request Body:**
```json
{
  "from": "+6281234567890",
  "message": "What is my current balance?",
  "id": "msg-unique-id-123",
  "channel": "whatsapp",
  "to": "+6287654321"
}
```

**Response (Success):**
```json
{
  "success": true,
  "reply": "Your current balance is Rp 5,000,000",
  "to": "+6281234567890",
  "channel": "whatsapp",
  "user": {
    "id": 1,
    "name": "John Doe"
  }
}
```

**Response (Unregistered User):**
```json
{
  "success": true,
  "reply": "Maaf, nomor WhatsApp Anda belum terdaftar...",
  "to": "+6281234567890",
  "channel": "whatsapp",
  "unregistered": true
}
```

**Response (Error):**
```json
{
  "success": false,
  "error": "Invalid signature"
}
```

### 2. Webhook Verification (GET)

**URL:** `GET /api/messaging/webhook`

For WhatsApp Business API webhook verification.

**Query Parameters:**
```
hub_challenge=challenge-value
hub_verify_token=your-verify-token
```

**Response:**
```json
{
  "challenge": "challenge-value"
}
```

### 3. Status Endpoint (GET)

**URL:** `GET /api/messaging/status`

Health check for webhook service.

**Response:**
```json
{
  "success": true,
  "service": "messaging_webhook",
  "version": "1.0.0",
  "timestamp": "2025-12-07T12:00:00.000000Z"
}
```

## Security

### HMAC SHA256 Signature

Calculate signature:

```php
$payload = json_encode($data);
$secret = env('MESSAGING_WEBHOOK_SECRET');
$signature = hash_hmac('sha256', $payload, $secret);
```

```javascript
// Node.js/n8n
const crypto = require('crypto');
const payload = JSON.stringify(data);
const secret = 'your-webhook-secret';
const signature = crypto
  .createHmac('sha256', secret)
  .update(payload)
  .digest('hex');
```

```python
# Python
import hmac
import hashlib
import json

payload = json.dumps(data)
secret = b'your-webhook-secret'
signature = hmac.new(secret, payload.encode(), hashlib.sha256).hexdigest()
```

### Message Deduplication

Messages with duplicate `external_id` + `channel` combination are automatically detected and skipped to prevent double-processing.

## n8n Integration Example

### Workflow Structure

```
Webhook Trigger (WhatsApp)
    ↓
Function Node (Calculate Signature)
    ↓
HTTP Request Node (Call Cashfloo Webhook)
    ↓
Function Node (Parse Response)
    ↓
WhatsApp Send Message Node
```

### Function Node (Calculate Signature)

```javascript
const crypto = require('crypto');

// Get webhook secret from n8n credentials
const secret = '{{$credentials.cashflooWebhook.secret}}';

// Prepare payload
const payload = {
  from: items[0].json.from,
  message: items[0].json.body,
  id: items[0].json.id,
  channel: 'whatsapp',
  to: items[0].json.to
};

const payloadString = JSON.stringify(payload);
const signature = crypto
  .createHmac('sha256', secret)
  .update(payloadString)
  .digest('hex');

return {
  json: {
    payload: payload,
    signature: signature
  }
};
```

### HTTP Request Node Configuration

- **Method:** POST
- **URL:** `https://your-domain.com/api/messaging/webhook`
- **Headers:**
  - `Content-Type`: `application/json`
  - `X-Webhook-Signature`: `{{$node["Function"].json["signature"]}}`
- **Body:** `{{$node["Function"].json["payload"]}}`

## Testing

### Run Tests

```bash
# All messaging tests
php artisan test --filter=MessagingWebhookTest

# Specific test
php artisan test --filter=test_webhook_accepts_valid_signature_and_processes_message
```

### Manual Testing

Run the test script:

```bash
php test-webhook.php
```

Or use cURL:

```bash
# Calculate signature
SECRET="your-webhook-secret"
PAYLOAD='{"from":"+6281234567890","message":"Hello","id":"test-123","channel":"whatsapp"}'
SIGNATURE=$(echo -n "$PAYLOAD" | openssl dgst -sha256 -hmac "$SECRET" | sed 's/^.* //')

# Send request
curl -X POST http://localhost:8000/api/messaging/webhook \
  -H "Content-Type: application/json" \
  -H "X-Webhook-Signature: $SIGNATURE" \
  -d "$PAYLOAD"
```

## API Endpoints Summary

| Endpoint | Method | Purpose | Auth |
|----------|--------|---------|------|
| `/api/messaging/webhook` | POST | Receive incoming messages | HMAC Signature |
| `/api/messaging/webhook` | GET | WhatsApp verification | Verify Token |
| `/api/messaging/status` | GET | Health check | None |
| `/settings/whatsapp` | POST | Set WhatsApp number | Session Auth |
| `/settings/whatsapp` | DELETE | Remove WhatsApp number | Session Auth |

## Monitoring & Logging

All webhook interactions are logged in:
- **Database:** `messages` table
- **Laravel Logs:** `storage/logs/laravel.log`

Monitor for:
- Failed signature validations
- Unregistered user attempts
- AI service errors
- Redis connection issues

## Troubleshooting

### Issue: "Invalid signature" errors

**Solution:**
1. Verify `MESSAGING_WEBHOOK_SECRET` matches in both systems
2. Ensure payload is not modified before signature calculation
3. Check that signature is calculated on raw JSON string
4. Verify header name is `X-Webhook-Signature` (case-insensitive)

### Issue: "User not found" / Unregistered user response

**Solution:**
1. Ensure user has set `whatsapp_number` in account settings
2. Verify phone number format is E.164 (starts with +)
3. Check phone number normalization (spaces, dashes removed)

### Issue: Redis errors

**Solution:**
- Redis is optional; system falls back to MySQL
- Install PHP Redis extension for better performance
- Check Redis connection in `.env`

### Issue: Slow responses

**Solutions:**
1. Enable Redis for faster history retrieval
2. Reduce `PROMPT_MESSAGE_LIMIT` (default: 20)
3. Add database indexes if needed
4. Consider caching user lookups

## Future Enhancements

- [ ] Token-based history trimming (instead of message count)
- [ ] Multi-language support detection
- [ ] Attachment handling (images, documents)
- [ ] Conversation analytics dashboard
- [ ] Rate limiting per user
- [ ] Webhook retry mechanism
- [ ] Support for Telegram, SMS, Facebook Messenger
- [ ] AI model selection per user
- [ ] Custom AI instructions per user

## Support

For issues or questions, contact the development team or create an issue in the repository.
