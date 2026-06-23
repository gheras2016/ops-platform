<?php

namespace App\Services;

use App\Models\DeviceToken;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Sends push notifications via the Firebase Cloud Messaging HTTP v1 API. The
 * OAuth access token is minted by signing a JWT with the service-account key
 * (no SDK needed) and cached. Gracefully no-ops when FCM isn't configured, so
 * the app runs fine without it. Invalid/unregistered tokens are pruned.
 */
class FcmSender
{
    private const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    public function send(array $tokens, string $title, string $body, array $data = []): void
    {
        $tokens = array_values(array_filter(array_unique($tokens)));
        $creds = $this->credentials();
        if (empty($tokens) || ! $creds) {
            return;
        }

        $access = $this->accessToken($creds);
        if (! $access) {
            return;
        }

        $url = "https://fcm.googleapis.com/v1/projects/{$creds['project_id']}/messages:send";
        $data = array_map(fn ($v) => (string) $v, $data); // FCM data values must be strings

        foreach ($tokens as $token) {
            $res = Http::withToken($access)->post($url, [
                'message' => [
                    'token' => $token,
                    'notification' => ['title' => $title, 'body' => $body],
                    'data' => $data,
                    'android' => [
                        'priority' => 'high',
                        'notification' => ['channel_id' => 'ops_tickets', 'sound' => 'default'],
                    ],
                ],
            ]);

            // Drop tokens the gateway reports as gone.
            if ($res->status() === 404 || str_contains((string) $res->body(), 'UNREGISTERED')) {
                DeviceToken::where('token', $token)->delete();
            }
        }
    }

    private function accessToken(array $creds): ?string
    {
        return Cache::remember('fcm_access_token', 3300, function () use ($creds) {
            $tokenUri = $creds['token_uri'] ?? 'https://oauth2.googleapis.com/token';
            $now = time();

            $jwt = $this->b64(json_encode(['alg' => 'RS256', 'typ' => 'JWT']))
                . '.' . $this->b64(json_encode([
                    'iss' => $creds['client_email'],
                    'scope' => self::SCOPE,
                    'aud' => $tokenUri,
                    'iat' => $now,
                    'exp' => $now + 3600,
                ]));

            openssl_sign($jwt, $signature, $creds['private_key'], OPENSSL_ALGO_SHA256);
            $assertion = $jwt . '.' . $this->b64($signature);

            $res = Http::asForm()->post($tokenUri, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $assertion,
            ]);

            return $res->json('access_token');
        });
    }

    /** Decode the service-account credentials from env (inline JSON or a path). */
    private function credentials(): ?array
    {
        $json = config('services.fcm.credentials');
        if (! $json && ($path = config('services.fcm.credentials_path')) && is_file($path)) {
            $json = file_get_contents($path);
        }
        if (! $json) {
            return null;
        }

        $c = json_decode($json, true);

        return is_array($c) && ! empty($c['private_key']) && ! empty($c['client_email']) && ! empty($c['project_id'])
            ? $c : null;
    }

    private function b64(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
