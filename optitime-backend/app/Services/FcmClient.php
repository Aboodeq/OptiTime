<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

final class FcmClient
{
    public function __construct(
        private Client $http = new Client
    ) {}

    public function sendToToken(string $token, string $title, string $body, array $data = []): bool
    {
        $projectId = (string) config('services.firebase.project_id');
        $serviceAccountPath = (string) config('services.firebase.service_account_path');
        if ($projectId === '' || $serviceAccountPath === '' || ! is_file($serviceAccountPath)) {
            return false;
        }

        $accessToken = $this->accessTokenFromServiceAccount($serviceAccountPath);
        if ($accessToken === null) {
            return false;
        }

        $payload = [
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => $this->normalizeData($data),
                'webpush' => [
                    'fcm_options' => [
                        'link' => (string) ($data['link'] ?? ''),
                    ],
                ],
            ],
        ];

        try {
            $this->http->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                'headers' => [
                    'Authorization' => 'Bearer '.$accessToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
                'timeout' => 8,
            ]);

            return true;
        } catch (GuzzleException $e) {
            Log::warning('FCM send failed', ['message' => $e->getMessage()]);

            return false;
        }
    }

    private function accessTokenFromServiceAccount(string $path): ?string
    {
        $raw = file_get_contents($path);
        if ($raw === false) {
            return null;
        }
        $creds = json_decode($raw, true);
        if (! is_array($creds)) {
            return null;
        }

        $clientEmail = (string) ($creds['client_email'] ?? '');
        $privateKey = (string) ($creds['private_key'] ?? '');
        $tokenUri = (string) ($creds['token_uri'] ?? 'https://oauth2.googleapis.com/token');
        if ($clientEmail === '' || $privateKey === '') {
            return null;
        }

        $now = time();
        $header = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']) ?: '{}');
        $claimSet = $this->base64UrlEncode(json_encode([
            'iss' => $clientEmail,
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => $tokenUri,
            'iat' => $now,
            'exp' => $now + 3600,
        ]) ?: '{}');

        $unsigned = $header.'.'.$claimSet;
        $signature = '';
        $ok = openssl_sign($unsigned, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        if (! $ok) {
            return null;
        }
        $jwt = $unsigned.'.'.$this->base64UrlEncode($signature);

        try {
            $res = $this->http->post($tokenUri, [
                'form_params' => [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt,
                ],
                'timeout' => 8,
            ]);
            $payload = json_decode((string) $res->getBody(), true);
            if (! is_array($payload)) {
                return null;
            }

            return isset($payload['access_token']) ? (string) $payload['access_token'] : null;
        } catch (GuzzleException) {
            return null;
        }
    }

    private function normalizeData(array $data): array
    {
        $out = [];
        foreach ($data as $k => $v) {
            if ($v === null) {
                continue;
            }
            $out[(string) $k] = is_scalar($v) ? (string) $v : json_encode($v);
        }

        return $out;
    }

    private function base64UrlEncode(string $input): string
    {
        return rtrim(strtr(base64_encode($input), '+/', '-_'), '=');
    }
}
