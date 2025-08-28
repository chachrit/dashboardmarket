# Dashboard Market API Documentation

## Base URL
```
https://journal.co.th/dashboardmarket/
```

## Endpoints

### 1. Test Connection
**URL:** `/api.php?action=test_connection&platform={platform}`
**Method:** GET
**Parameters:**
- `platform`: shopee|lazada|tiktok
- `env`: sandbox|prod (for Shopee only)

**Response:**
```json
{
  "success": true|false,
  "message": "Connection status message"
}
```

### 2. Save Settings
**URL:** `/api.php?action=save_settings&platform={platform}`
**Method:** POST
**Body:** JSON with platform credentials

**Response:**
```json
{
  "success": true|false,
  "message": "Save status message"
}
```

### 3. Get Orders
**URL:** `/api.php?action=getOrders&platform={platform}`
**Method:** GET
**Parameters:**
- `platform`: shopee|lazada|tiktok
- `date_from`: YYYY-MM-DD (optional)
- `date_to`: YYYY-MM-DD (optional)
- `limit`: number (optional, default 50)

**Response:**
```json
{
  "success": true,
  "data": {
    "total_sales": 12345.67,
    "total_orders": 10,
    "orders": [...]
  }
}
```

## OAuth Callbacks

### Shopee OAuth
**URL:** `/shopee_callback.php`
**Method:** GET
**Parameters:**
- `code`: authorization code from Shopee
- `shop_id`: shop ID from Shopee
- `state`: optional state parameter

### Lazada OAuth
**URL:** `/lazada_callback.php`
**Method:** GET
**Parameters:**
- `code`: authorization code from Lazada
- `state`: optional state parameter

## Authentication

All API calls require proper credentials to be saved in the system first:

### Shopee Required:
- Partner ID (6-8 digits)
- Partner Key (secret)
- Shop ID
- Access Token (from OAuth)

### Lazada Required:
- App Key
- App Secret  
- Access Token (from OAuth)

### TikTok Required:
- Client Key
- Client Secret
- Access Token (from OAuth)

## Error Responses

```json
{
  "success": false,
  "error": "Error description",
  "message": "User-friendly error message"
}
```

## Rate Limits

Follow each platform's rate limiting:
- **Shopee:** 1000 calls/hour per shop
- **Lazada:** 10000 calls/day per app
- **TikTok:** 1000 calls/hour per app

## Security

- All endpoints require HTTPS
- Sensitive data (tokens, secrets) are stored securely
- OAuth flows follow platform specifications

## Contact

For technical support: https://journal.co.th/dashboardmarket/debug_api.php
