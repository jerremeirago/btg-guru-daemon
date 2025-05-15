# API Endpoints

## Public Endpoints

### GET /api/v1/health
Returns a simple response indicating that the server is running.

Response:
```json
{
    "status": "ok"
}
```

### POST /api/v1/login
Authenticate a user and get an access token.

Payload:
```json
{
    "email": "test@example.com",
    "password": "password"
}
```

Response:
```json
{
    "token": "your_token_here",
    "email": "test@example.com"
}
```

## Protected Endpoints
All protected endpoints require a valid Sanctum token to be included in the request headers:

```
Authorization: Bearer your_token_here
```

### POST /api/v1/logout
Revoke the current access token.

Response:
```json
{
    "message": "Successfully logged out"
}
```

### GET /api/v1/user/profile
Get the authenticated user's profile information.

Response:
```json
{
    "id": 1,
    "name": "John Doe",
    "email": "test@example.com",
    "subscription_tier": "premium",
    "has_active_subscription": true,
    "initials": "JD"
}
```

### GET /api/v1/user/subscription
Get the authenticated user's subscription details.

Response:
```json
{
    "tier": "premium",
    "active": true,
    "expires_at": "2025-12-31T23:59:59+00:00"
}
```