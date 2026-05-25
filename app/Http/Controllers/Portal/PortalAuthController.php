<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PortalAuthController extends Controller
{
    public function identity(Request $request): JsonResponse
    {
        $startedAt = microtime(true);
        $identityRaw = (string) $request->input('identity', '');
        $identity = preg_replace('/\D+/', '', $identityRaw) ?: '';
        $context = [
            'identity_present' => trim($identityRaw) !== '',
            'identity_last4' => $identity !== '' ? substr($identity, -4) : null,
        ];

        $validator = Validator::make(['identity' => $identity], [
            'identity' => ['required', 'string', 'min:8', 'max:20'],
        ]);

        if ($validator->fails()) {
            Log::warning('Portal identity auth rejected before ERP request.', array_merge($context, [
                'validation_failed' => true,
                'total_elapsed_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ]));

            return response()->json($this->authResponse(false));
        }

        $path = '/'.ltrim((string) config('portal.erp_b2b_auth_identity_path', '/api/portal-b2b/auth/identity'), '/');
        $endpoint = rtrim((string) config('portal.erp_base_url', 'http://localhost:8001'), '/').$path;

        try {
            $response = Http::timeout((int) config('portal.erp_timeout', 15))
                ->acceptJson()
                ->post($endpoint, ['identity' => $identityRaw]);

            if (! $response->successful() || ! str_contains((string) $response->header('Content-Type'), 'application/json')) {
                Log::warning('Portal identity auth ERP request rejected.', array_merge($context, [
                    'status' => $response->status(),
                    'total_elapsed_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                ]));

                return response()->json($this->authResponse(false));
            }

            $payload = $response->json();
            $authenticated = (bool) data_get($payload, 'data.authenticated', false);
            $client = data_get($payload, 'data.client');
            $fastevoB2BToken = (string) data_get($payload, 'data.token', '');

            if (! $authenticated || ! is_array($client) || $fastevoB2BToken === '') {
                Log::warning('Portal identity auth failed.', array_merge($context, [
                    'authenticated' => false,
                    'total_elapsed_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                ]));

                return response()->json($this->authResponse(false, null, $payload['meta'] ?? []));
            }

            $issuedAt = now();
            $expiresAt = $issuedAt->copy()->addHours(8);
            $token = Crypt::encryptString(json_encode([
                'fastevo_b2b_token' => $fastevoB2BToken,
                'client_name' => (string) ($client['name'] ?? 'Cliente B2B'),
                'client_code' => (string) ($client['code'] ?? ''),
                'issued_at' => $issuedAt->toIso8601String(),
                'expires_at' => $expiresAt->toIso8601String(),
            ], JSON_THROW_ON_ERROR));

            Log::info('Portal identity auth completed.', array_merge($context, [
                'authenticated' => true,
                'b2b_token_present' => true,
                'token_issued' => true,
                'total_elapsed_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ]));

            return response()->json($this->authResponse(true, [
                'token' => $token,
                'client' => [
                    'name' => (string) ($client['name'] ?? 'Cliente B2B'),
                    'code' => (string) ($client['code'] ?? ''),
                ],
                'expiresAt' => $expiresAt->toIso8601String(),
            ], $payload['meta'] ?? []));
        } catch (\Throwable $exception) {
            Log::warning('Portal identity auth exception.', array_merge($context, [
                'error' => $exception->getMessage(),
                'total_elapsed_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ]));

            return response()->json($this->authResponse(false));
        }
    }

    private function authResponse(bool $authenticated, ?array $data = null, array $meta = []): array
    {
        return [
            'data' => array_merge([
                'authenticated' => $authenticated,
                'token' => null,
                'client' => null,
            ], $data ?? []),
            'meta' => array_merge(['source' => 'fastevo-public-b2b'], $meta),
        ];
    }
}
