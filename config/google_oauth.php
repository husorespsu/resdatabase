<?php

declare(strict_types=1);

/**
 * Google OAuth 2.0 configuration.
 *
 * All credentials are read from $_ENV (populated by vlucas/phpdotenv).
 *
 * Usage:
 *   $oauthConfig = require __DIR__ . '/google_oauth.php';
 *   $client->setClientId($oauthConfig['client_id']);
 */

// Parse the comma-separated allowed domains into an array.
$rawDomains    = $_ENV['ALLOWED_DOMAINS'] ?? 'psu.ac.th';
$allowedDomains = array_filter(
    array_map('trim', explode(',', $rawDomains))
);

return [

    // ── OAuth 2.0 Credentials ────────────────────────────────
    'client_id'     => $_ENV['GOOGLE_CLIENT_ID']     ?? '',
    'client_secret' => $_ENV['GOOGLE_CLIENT_SECRET'] ?? '',
    'redirect_uri'  => $_ENV['GOOGLE_REDIRECT_URI']  ?? 'http://localhost/research/auth/google/callback',

    // ── Requested Scopes ─────────────────────────────────────
    'scopes' => [
        'email',
        'profile',
    ],

    // ── Access Type ──────────────────────────────────────────
    // 'offline'  → receive a refresh_token so the app can act on behalf of
    //              the user without them being present (needed for long tasks).
    // 'online'   → simpler; no refresh_token.
    'access_type'    => 'online',

    // Prompt the user to select an account every time they log in.
    'prompt'         => 'select_account',

    // ── Domain Restriction ───────────────────────────────────
    // Only users whose email domain appears in this list may log in.
    'allowed_domains' => array_values($allowedDomains),

    // ── Google API Endpoints (for reference / manual flows) ──
    'auth_uri'       => 'https://accounts.google.com/o/oauth2/v2/auth',
    'token_uri'      => 'https://oauth2.googleapis.com/token',
    'userinfo_uri'   => 'https://www.googleapis.com/oauth2/v3/userinfo',

];
