# OAuth & JWT API Documentation

## Endpoints

### 1. Signup (Email/Password)
**POST** `/api/auth.php?path=signup`

Request:
```json
{
  "full_name": "John Doe",
  "email": "john@example.com",
  "password": "SecurePassword123"
}
```

Response:
```json
{
  "success": true,
  "token": "eyJhbGc...",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com"
  }
}
```

---

### 2. Login (Email/Password)
**POST** `/api/auth.php?path=login`

Request:
```json
{
  "email": "john@example.com",
  "password": "SecurePassword123"
}
```

Response:
```json
{
  "success": true,
  "token": "eyJhbGc...",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com"
  }
}
```

---

### 3. Google OAuth Authorization URL
**POST** `/api/auth.php?path=google/authorize`

Response:
```json
{
  "success": true,
  "authorization_url": "https://accounts.google.com/o/oauth2/v2/auth?...",
  "provider": "google"
}
```

Then redirect user to `authorization_url`. After user approves, they'll be redirected to:
```
/api/auth.php?path=google/callback&code=...&state=...
```

---

### 4. GitHub OAuth Authorization URL
**POST** `/api/auth.php?path=github/authorize`

Response:
```json
{
  "success": true,
  "authorization_url": "https://github.com/login/oauth/authorize?...",
  "provider": "github"
}
```

---

### 5. Verify Token
**POST** `/api/auth.php?path=verify`

Headers:
```
Authorization: Bearer eyJhbGc...
```

Response:
```json
{
  "success": true,
  "user": {
    "user_id": 1,
    "email": "john@example.com",
    "name": "John Doe",
    "provider": "google",
    "iat": 1694000000,
    "exp": 1694003600
  }
}
```

---

### 6. Logout
**POST** `/api/auth.php?path=logout`

Response:
```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

---

## Setup Instructions

### 1. Set JWT Secret in `.env`
```
JWT_SECRET=your-super-secret-key-here
```

### 2. Get Google OAuth Credentials
1. Go to https://console.cloud.google.com/
2. Create a new project
3. Enable "Google+ API"
4. Create OAuth 2.0 credentials (Web Application)
5. Add authorized redirect URI: `https://your-domain.com/api/auth.php?path=google/callback`
6. Copy Client ID and Secret to `.env`:
```
GOOGLE_CLIENT_ID=your-client-id
GOOGLE_CLIENT_SECRET=your-client-secret
```

### 3. Get GitHub OAuth Credentials
1. Go to https://github.com/settings/developers
2. Click "New OAuth App"
3. Authorization callback URL: `https://your-domain.com/api/auth.php?path=github/callback`
4. Copy Client ID and Secret to `.env`:
```
GITHUB_CLIENT_ID=your-client-id
GITHUB_CLIENT_SECRET=your-client-secret
```

### 4. Update Database (Add OAuth columns)
```sql
ALTER TABLE users ADD COLUMN oauth_provider VARCHAR(50) NULL;
ALTER TABLE users ADD COLUMN oauth_id VARCHAR(255) NULL;
```

---

## Example JavaScript Usage

```javascript
// Signup
const signup = async (fullName, email, password) => {
  const res = await fetch('/api/auth.php?path=signup', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ full_name: fullName, email, password })
  });
  const data = await res.json();
  localStorage.setItem('token', data.token);
  return data;
};

// Login
const login = async (email, password) => {
  const res = await fetch('/api/auth.php?path=login', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email, password })
  });
  const data = await res.json();
  localStorage.setItem('token', data.token);
  return data;
};

// Google OAuth
const loginWithGoogle = async () => {
  const res = await fetch('/api/auth.php?path=google/authorize', {
    method: 'POST'
  });
  const data = await res.json();
  window.location.href = data.authorization_url;
};

// GitHub OAuth
const loginWithGithub = async () => {
  const res = await fetch('/api/auth.php?path=github/authorize', {
    method: 'POST'
  });
  const data = await res.json();
  window.location.href = data.authorization_url;
};

// Verify Token
const verifyToken = async () => {
  const token = localStorage.getItem('token');
  const res = await fetch('/api/auth.php?path=verify', {
    method: 'POST',
    headers: { 'Authorization': `Bearer ${token}` }
  });
  return await res.json();
};

// Logout
const logout = async () => {
  localStorage.removeItem('token');
  await fetch('/api/auth.php?path=logout', { method: 'POST' });
};
```

---

## Error Handling

All endpoints return errors like:
```json
{
  "success": false,
  "error": "Invalid credentials"
}
```

HTTP status codes:
- `200` - Success
- `400` - Bad request / Invalid data
- `401` - Unauthorized / Invalid token
