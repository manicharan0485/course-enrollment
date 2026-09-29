<?php
/**
 * OAuth Configuration
 * Supported: Google, GitHub
 */

return [
    'google' => [
        'client_id' => getenv('GOOGLE_CLIENT_ID') ?: '',
        'client_secret' => getenv('GOOGLE_CLIENT_SECRET') ?: '',
        'redirect_uri' => getenv('APP_URL') . 'api/auth/google/callback',
        'auth_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
        'token_url' => 'https://www.googleapis.com/oauth2/v4/token',
        'userinfo_url' => 'https://www.googleapis.com/oauth2/v2/userinfo',
    ],
    
    'github' => [
        'client_id' => getenv('GITHUB_CLIENT_ID') ?: '',
        'client_secret' => getenv('GITHUB_CLIENT_SECRET') ?: '',
        'redirect_uri' => getenv('APP_URL') . 'api/auth/github/callback',
        'auth_url' => 'https://github.com/login/oauth/authorize',
        'token_url' => 'https://github.com/login/oauth/access_token',
        'userinfo_url' => 'https://api.github.com/user',
    ],
];
