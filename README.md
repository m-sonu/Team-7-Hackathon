# Team-7-Hackathon

## API Authentication with Socialite

This project includes a fully stateless API authentication flow using Laravel Socialite and Sanctum.

### Endpoints

#### 1. Get Redirect URL
`GET /api/auth/{provider}/redirect`

Use this endpoint to get the OAuth authorization URL for a specific provider (e.g., `google`, `github`).
**Response:**
```json
{
    "url": "https://accounts.google.com/o/oauth2/auth?client_id=..."
}
```

#### 2. Handle Callback (Code Exchange)
`GET /api/auth/{provider}/callback`

The provider will redirect the user back to this endpoint after successful authentication. The backend will exchange the authorization code for an access token, log the user in, and return a Sanctum API token.
**Response:**
```json
{
    "message": "Successfully authenticated",
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "provider_name": "google",
        "provider_id": "123456789"
    },
    "token": "1|sanctum_token_string",
    "token_type": "Bearer"
}
```

#### 3. Handle Callback (Token Exchange)
`POST /api/auth/{provider}/callback`

If your frontend handles the OAuth flow directly (e.g., using Google Sign-In SDK on mobile) and obtains an access token, you can send it to this endpoint to log the user in and receive a Sanctum token.
**Request Body:**
```json
{
    "access_token": "provider_access_token_here"
}
```
**Response:** Same as the `GET` callback above.

### Setup

1. Configure your OAuth provider credentials in the `.env` file:
```env
GITHUB_CLIENT_ID=your_github_client_id
GITHUB_CLIENT_SECRET=your_github_client_secret
GITHUB_REDIRECT_URI=http://localhost:8000/api/auth/github/callback

GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URI=http://localhost:8000/api/auth/google/callback
```
2. The `password` field in the `users` table is nullable to accommodate users signing up purely via Socialite.
