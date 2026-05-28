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

        $validator = Validator::make([
            'identity' => $identity,
            'password' => (string) $request->input('password', ''),
        ], [
            'identity' => ['required', 'string', 'min:8', 'max:20'],
            'password' => ['nullable', 'string', 'max:100'],
        ]);

        if ($validator->fails()) {
            Log::warning('Portal identity auth rejected before ERP request.', array_merge($context, [
                'validation_failed' => true,
                'total_elapsed_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ]));

            return response()->json($this->authResponse(false));
        }

        $endpoint = rtrim((string) config('portal.fastevo.base_url'), '/')
            .'/'.ltrim((string) config('portal.fastevo.paths.auth_identity'), '/');
        $baseHost = parse_url((string) config('portal.fastevo.base_url'), PHP_URL_HOST) ?: 'unknown';

        try {
            $response = Http::timeout((int) config('portal.fastevo.timeout', 15))
                ->withOptions(['verify' => (bool) config('portal.fastevo.verify_ssl', true)])
                ->acceptJson()
                ->post($endpoint, array_filter([
                    'identity' => $identityRaw,
                    'password' => (string) $request->input('password', ''),
                ], fn ($value) => $value !== ''));

            if (! $response->successful() || ! str_contains((string) $response->header('Content-Type'), 'application/json')) {
                Log::warning('Portal identity auth ERP request rejected.', array_merge($context, [
                    'base_host' => $baseHost,
                    'path_key' => 'auth_identity',
                    'ssl_verify' => (bool) config('portal.fastevo.verify_ssl', true),
                    'status' => $response->status(),
                    'content_type' => (string) $response->header('Content-Type'),
                    'total_elapsed_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                ]));

                return response()->json($this->authResponse(false, [
                    'message' => 'No se pudo validar la identidad en este momento.',
                ]), 502);
            }

            $payload = $response->json();
            $authenticated = (bool) data_get($payload, 'data.authenticated', false);
            $client = data_get($payload, 'data.client');
            $fastevoB2BToken = (string) data_get($payload, 'data.token', '');

            if (! $authenticated || ! is_array($client) || $fastevoB2BToken === '') {
                Log::warning('Portal identity auth failed.', array_merge($context, [
                    'authenticated' => false,
                    'requires_password' => (bool) data_get($payload, 'data.requiresPassword', false),
                    'total_elapsed_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                ]));

                return response()->json($this->authResponse(false, [
                    'requiresPassword' => (bool) data_get($payload, 'data.requiresPassword', false),
                    'message' => (string) data_get($payload, 'data.message', ''),
                ], $payload['meta'] ?? []));
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
                'base_host' => $baseHost,
                'path_key' => 'auth_identity',
                'ssl_verify' => (bool) config('portal.fastevo.verify_ssl', true),
                'error' => $exception->getMessage(),
                'total_elapsed_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ]));

            return response()->json($this->authResponse(false, [
                'message' => 'No se pudo validar la identidad en este momento.',
            ]), 502);
        }
    }

    public function checkIdentity(Request $request): JsonResponse
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
            Log::warning('Portal password identity check rejected before ERP request.', array_merge($context, [
                'validation_failed' => true,
                'total_elapsed_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ]));

            return response()->json($this->passwordResponse([
                'exists' => false,
                'hasPassword' => false,
                'canCreatePassword' => false,
                'client' => null,
            ]));
        }

        return $this->forwardPasswordRequest('auth_check_identity', ['identity' => $identityRaw], $context, $startedAt, 'check');
    }

    public function createPassword(Request $request): JsonResponse
    {
        $startedAt = microtime(true);
        $identityRaw = (string) $request->input('identity', '');
        $identity = preg_replace('/\D+/', '', $identityRaw) ?: '';
        $context = [
            'identity_present' => trim($identityRaw) !== '',
            'identity_last4' => $identity !== '' ? substr($identity, -4) : null,
        ];

        $validator = Validator::make([
            'identity' => $identity,
            'password' => (string) $request->input('password', ''),
            'password_confirmation' => (string) $request->input('password_confirmation', ''),
        ], [
            'identity' => ['required', 'string', 'min:8', 'max:20'],
            'password' => ['required', 'string', 'min:8', 'max:100', 'confirmed', 'regex:/[A-Za-z]/', 'regex:/[0-9]/'],
        ]);

        if ($validator->fails()) {
            Log::warning('Portal password creation rejected before ERP request.', array_merge($context, [
                'validation_failed' => true,
                'total_elapsed_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ]));

            return response()->json($this->passwordResponse([
                'created' => false,
                'message' => 'Revise identidad y confirmacion de contrasena.',
            ]), 422);
        }

        return $this->forwardPasswordRequest('auth_create_password', [
            'identity' => $identityRaw,
            'password' => (string) $request->input('password', ''),
            'password_confirmation' => (string) $request->input('password_confirmation', ''),
        ], $context, $startedAt, 'create');
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

    private function forwardPasswordRequest(string $pathKey, array $payload, array $context, float $startedAt, string $operation): JsonResponse
    {
        $endpoint = rtrim((string) config('portal.fastevo.base_url'), '/')
            .'/'.ltrim((string) config("portal.fastevo.paths.{$pathKey}"), '/');
        $baseHost = parse_url((string) config('portal.fastevo.base_url'), PHP_URL_HOST) ?: 'unknown';

        try {
            $response = Http::timeout((int) config('portal.fastevo.timeout', 15))
                ->withOptions(['verify' => (bool) config('portal.fastevo.verify_ssl', true)])
                ->acceptJson()
                ->post($endpoint, $payload);

            if (! str_contains((string) $response->header('Content-Type'), 'application/json')) {
                Log::warning("Portal password {$operation} ERP response was not JSON.", array_merge($context, [
                    'base_host' => $baseHost,
                    'path_key' => $pathKey,
                    'ssl_verify' => (bool) config('portal.fastevo.verify_ssl', true),
                    'status' => $response->status(),
                    'content_type' => (string) $response->header('Content-Type'),
                    'total_elapsed_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                ]));

                return response()->json($this->passwordResponse([
                    'created' => false,
                    'message' => 'No se pudo procesar la solicitud en este momento.',
                ]), 502);
            }

            Log::info("Portal password {$operation} ERP request completed.", array_merge($context, [
                'base_host' => $baseHost,
                'path_key' => $pathKey,
                'ssl_verify' => (bool) config('portal.fastevo.verify_ssl', true),
                'status' => $response->status(),
                'content_type' => (string) $response->header('Content-Type'),
                'total_elapsed_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ]));

            return response()->json($response->json(), $response->status());
        } catch (\Throwable $exception) {
            Log::warning("Portal password {$operation} ERP request exception.", array_merge($context, [
                'base_host' => $baseHost,
                'path_key' => $pathKey,
                'ssl_verify' => (bool) config('portal.fastevo.verify_ssl', true),
                'error' => $exception->getMessage(),
                'total_elapsed_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ]));

            return response()->json($this->passwordResponse([
                'created' => false,
                'message' => 'No se pudo procesar la solicitud en este momento.',
            ]), 502);
        }
    }

    private function passwordResponse(array $data, array $meta = []): array
    {
        return [
            'data' => $data,
            'meta' => array_merge(['source' => 'fastevo-public-b2b'], $meta),
        ];
    }
}
