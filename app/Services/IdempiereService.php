<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class IdempiereService
{
    protected $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('idempiere.api.base_url'), '/');
    }

    /**
     * Authenticate user and retrieve JWT token.
     */
    public function login(string $username, string $password)
    {
        try {
            $response = Http::withoutVerifying()->post("{$this->baseUrl}/auth/tokens", [
                'userName' => $username,
                'password' => $password,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('iDempiere Login Failed: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('iDempiere Connection Error: ' . $e->getMessage());
            return null;
        }
    }

    public function loginWithContext(string $username, string $password, int $clientId, int $roleId, int $organizationId, int $warehouseId = null)
    {
        try {
            $payload = [
                'userName' => $username,
                'password' => $password,
                'parameters' => [
                    'clientId' => $clientId,
                    'roleId' => $roleId,
                    'organizationId' => $organizationId,
                    'language' => 'en_US'
                ]
            ];

            if ($warehouseId) {
                $payload['parameters']['warehouseId'] = $warehouseId;
            }

            $response = Http::withoutVerifying()->post("{$this->baseUrl}/auth/tokens", $payload);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('iDempiere Login Context Failed: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('iDempiere Connection Context Error: ' . $e->getMessage());
            return null;
        }
    }

    private function refreshToken()
    {
        try {
            Log::info('Attempting to refresh token via Re-Login (auth/tokens)...');

            $username = Session::get('idempiere_username');
            $password = Session::get('idempiere_auth_pwd');

            $clientId = Session::get('idempiere_client');
            $roleId = Session::get('idempiere_role');
            $orgId = Session::get('idempiere_org');
            $warehouseId = Session::get('idempiere_warehouse');

            if (!$username || !$password || !$clientId || !$roleId || !$orgId) {
                Log::warning('Cannot refresh token: Missing credentials or context in session.');
                return false;
            }

            $payload = [
                'userName' => $username,
                'password' => $password,
                'parameters' => [
                    'clientId' => (int) $clientId,
                    'roleId' => (int) $roleId,
                    'organizationId' => (int) $orgId,
                    'language' => 'en_US'
                ]
            ];

            if ($warehouseId) {
                $payload['parameters']['warehouseId'] = (int) $warehouseId;
            }

            $response = Http::withoutVerifying()->post("{$this->baseUrl}/auth/tokens", $payload);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['token'])) {
                    Session::put('api_token', $data['token']);
                    Session::save();
                    Log::info('Token refreshed successfully via re-login.');
                    return true;
                }
            }

            Log::warning('Token refresh (re-login) failed: ' . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error('Token Refresh (Re-Login) Exception: ' . $e->getMessage());
            return false;
        }
    }

    private function executeWithRetry(callable $callback)
    {
        $response = $callback();

        $isExpired = $response->status() === 401;
        if (!$isExpired && !$response->successful()) {
            $body = $response->body();
            if (stripos($body, 'expired') !== false || stripos($body, 'Invalid token') !== false) {
                $isExpired = true;
            }
        }

        if ($isExpired) {
            Log::info('Token expired (401/Body). Refreshing...');
            if ($this->refreshToken()) {
                Log::info('Token Refreshed. Retrying request...');
                return $callback();
            }
        }
        return $response;
    }

    /**
     * Static method for controllers that make direct Http calls.
     * Wraps a callable with automatic token refresh on 401.
     * The callable receives the current token as parameter.
     */
    public static function withAutoRetry(callable $callback)
    {
        $token = Session::get('api_token');
        $response = $callback($token);

        $isExpired = $response->status() === 401;
        if (!$isExpired && !$response->successful()) {
            $body = $response->body();
            if (stripos($body, 'expired') !== false || stripos($body, 'Invalid token') !== false) {
                $isExpired = true;
            }
        }

        if ($isExpired) {
            Log::info('Token expired (401/Body). Attempting re-login refresh...');
            if (self::staticRefreshToken()) {
                $newToken = Session::get('api_token');
                Log::info('Token Refreshed. Retrying request...');
                return $callback($newToken);
            }
        }

        return $response;
    }

    private static function staticRefreshToken()
    {
        try {
            Log::info('Static: Attempting to refresh token via Re-Login (auth/tokens)...');

            $username = Session::get('idempiere_username');
            $password = Session::get('idempiere_auth_pwd');
            $clientId = Session::get('idempiere_client');
            $roleId = Session::get('idempiere_role');
            $orgId = Session::get('idempiere_org');
            $warehouseId = Session::get('idempiere_warehouse');

            if (!$username || !$password || !$clientId || !$roleId || !$orgId) {
                Log::warning('Cannot refresh token: Missing credentials or context in session.');
                return false;
            }

            $payload = [
                'userName' => $username,
                'password' => $password,
                'parameters' => [
                    'clientId' => (int) $clientId,
                    'roleId' => (int) $roleId,
                    'organizationId' => (int) $orgId,
                    'language' => 'en_US'
                ]
            ];

            if ($warehouseId) {
                $payload['parameters']['warehouseId'] = (int) $warehouseId;
            }

            $baseUrl = rtrim((string) config('idempiere.api.base_url'), '/');
            $response = Http::withoutVerifying()->post("{$baseUrl}/auth/tokens", $payload);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['token'])) {
                    Session::put('api_token', $data['token']);
                    Session::save();
                    Log::info('Static: Token refreshed successfully via re-login.');
                    return true;
                }
            }

            Log::warning('Static: Token refresh (re-login) failed: ' . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error('Static: Token Refresh (Re-Login) Exception: ' . $e->getMessage());
            return false;
        }
    }

    public function post(string $endpoint, array $data)
    {
        return $this->executeWithRetry(function () use ($endpoint, $data) {
            $token = Session::get('api_token');
            return Http::withoutVerifying()->withToken($token)->post("{$this->baseUrl}/{$endpoint}", $data);
        });
    }

    public function put(string $endpoint, array $data)
    {
        return $this->executeWithRetry(function () use ($endpoint, $data) {
            $token = Session::get('api_token');
            return Http::withoutVerifying()->withToken($token)->put("{$this->baseUrl}/{$endpoint}", $data);
        });
    }

    public function get(string $endpoint, array $params = [])
    {
        return $this->executeWithRetry(function () use ($endpoint, $params) {
            $token = Session::get('api_token');
            return Http::withoutVerifying()->withToken($token)->get("{$this->baseUrl}/{$endpoint}", $params);
        });
    }

    public function uploadFile(string $endpoint, $file, $filename, $mimeType = null)
    {
        return $this->executeWithRetry(function () use ($endpoint, $file, $filename) {
            $token = Session::get('api_token');
            // Check if file is valid object or path
            $content = is_string($file) && file_exists($file) ? file_get_contents($file) : file_get_contents($file->getRealPath());

            // JSON Payload with Base64 as per user request
            $payload = [
                'name' => $filename,
                'data' => base64_encode($content)
            ];

            return Http::withoutVerifying()->withToken($token)
                ->post("{$this->baseUrl}/{$endpoint}", $payload);
        });
    }

    public function delete(string $endpoint)
    {
        return $this->executeWithRetry(function () use ($endpoint) {
            $token = Session::get('api_token');
            return Http::withoutVerifying()->withToken($token)->delete("{$this->baseUrl}/{$endpoint}");
        });
    }
}
