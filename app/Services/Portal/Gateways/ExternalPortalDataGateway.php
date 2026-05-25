<?php

namespace App\Services\Portal\Gateways;

use App\Services\Portal\Contracts\PortalDataGateway;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExternalPortalDataGateway implements PortalDataGateway
{
    private const CATALOG_CACHE_TTL_MINUTES = 5;
    private const DASHBOARD_CACHE_TTL_MINUTES = 2;
    private const ACCOUNT_CACHE_TTL_MINUTES = 2;
    private const INVOICES_CACHE_TTL_MINUTES = 2;
    private const ORDERS_CACHE_TTL_MINUTES = 2;
    private const QUOTES_CACHE_TTL_MINUTES = 2;
    private const FILTER_WARMUP_PER_PAGE = 100;
    private const FILTER_WARMUP_PAGES_PER_CYCLE = 5;

    public function __construct(private ?PortalDataGateway $fallback = null)
    {
        $this->fallback ??= new MockPortalDataGateway();
    }

    /**
     * Placeholder for the future external platform bridge.
     *
     * This portal must not own or persist portal business data. The eventual
     * implementation should fetch from the external module, normalize the
     * payload here, and return the stable contracts consumed by Vue.
     */
    public function dashboard(array $filters = []): array
    {
        $cached = $this->cachedDashboardPayload();

        if ($cached) {
            return $this->normalizeB2BDashboard($cached['payload'], $cached['source']);
        }

        $fallback = $this->fallback->dashboard($filters);
        $fallback['_source'] = 'mock-fallback';

        return $fallback;
    }

    private function cachedDashboardPayload(): ?array
    {
        $scope = $this->portalB2BCacheScope();
        $clientConfigured = $scope !== '';
        $cacheKey = 'portal.dashboard.'.sha1($this->erpBaseUrl().'|'.$this->b2bDashboardPath().'|'.$scope);
        $lastCacheKey = $cacheKey.'.last';

        if (Cache::has($cacheKey)) {
            Log::info('Portal dashboard served from fresh cache.', ['source' => 'external-cache', 'b2b_endpoint' => true, 'b2b_session_present' => $clientConfigured]);

            return ['payload' => Cache::get($cacheKey), 'source' => 'external-cache'];
        }

        $payload = $this->requestB2B($this->b2bDashboardPath());

        if ($payload) {
            Cache::put($cacheKey, $payload, now()->addMinutes(self::DASHBOARD_CACHE_TTL_MINUTES));
            Cache::put($lastCacheKey, $payload, now()->addHours(2));
            Log::info('Portal dashboard served from ERP B2B API and cached.', ['source' => 'external', 'b2b_endpoint' => true, 'b2b_session_present' => $clientConfigured]);

            return ['payload' => $payload, 'source' => 'external'];
        }

        $lastPayload = Cache::get($lastCacheKey);

        if (is_array($lastPayload)) {
            Log::warning('Portal dashboard ERP B2B API failed; served stale cache.', ['source' => 'external-cache', 'b2b_endpoint' => true, 'b2b_session_present' => $clientConfigured]);

            return ['payload' => $lastPayload, 'source' => 'external-cache'];
        }

        Log::warning('Portal dashboard ERP B2B API failed; no cache available, using mock fallback.', ['source' => 'mock-fallback', 'b2b_endpoint' => true, 'b2b_session_present' => $clientConfigured]);

        return null;
    }

    public function catalog(array $filters = []): array
    {
        $query = [
            'page' => max(1, (int) ($filters['page'] ?? 1)),
            'per_page' => $this->perPage($filters['per_page'] ?? 24),
            'search' => $this->activeFilterValue($filters['search'] ?? null),
            'category' => $this->activeFilterValue($filters['category'] ?? null),
            'brand' => $this->activeFilterValue($filters['brand'] ?? null),
            'max_price' => $this->activeFilterValue($filters['max_price'] ?? null),
            'availability' => $this->activeFilterValue($filters['availability'] ?? null, ['all']),
            'include_filters' => $this->includeFiltersValue($filters['include_filters'] ?? null),
        ];
        $cached = $this->cachedCatalogPayload($query);

        if ($cached) {
            return $this->normalizeCatalog($cached['payload'], $filters, $cached['source']);
        }

        $fallback = $this->fallback->catalog($filters);
        $fallback['_source'] = 'mock-fallback';

        return $fallback;
    }

    private function cachedCatalogPayload(array $query): ?array
    {
        $query = array_filter($query, fn ($value): bool => $value !== null && $value !== '');
        $cacheKey = 'portal.catalog.page.'.sha1($this->b2bCatalogCacheScope().'|'.$this->portalB2BCacheScope().'|'.json_encode($query));
        $lastCacheKey = $cacheKey.'.last';

        if (Cache::has($cacheKey)) {
            Log::info('Portal catalog served from fresh cache.', ['query' => $query, 'source' => 'external-cache', 'b2b_catalog_endpoint' => true]);

            return ['payload' => Cache::get($cacheKey), 'source' => 'external-cache'];
        }

        $payload = $this->requestB2BCatalog($query);

        if ($payload) {
            Cache::put($cacheKey, $payload, now()->addMinutes(self::CATALOG_CACHE_TTL_MINUTES));
            Cache::put($lastCacheKey, $payload, now()->addHours(6));
            Log::info('Portal catalog served from ERP B2B API and cached.', ['query' => $query, 'source' => 'external', 'b2b_catalog_endpoint' => true]);

            return ['payload' => $payload, 'source' => 'external'];
        }

        $lastPayload = Cache::get($lastCacheKey);

        if (is_array($lastPayload)) {
            Log::warning('Portal catalog ERP B2B API failed; served stale cache.', ['query' => $query, 'source' => 'external-cache', 'b2b_catalog_endpoint' => true]);

            return ['payload' => $lastPayload, 'source' => 'external-cache'];
        }

        Log::warning('Portal catalog ERP B2B API failed; no cache available, using mock fallback.', ['query' => $query, 'source' => 'mock-fallback', 'b2b_catalog_endpoint' => true]);

        return null;
    }

    private function requestB2BCatalog(array $query = []): ?array
    {
        $path = $this->b2bProductsPath();
        $endpoint = $this->erpBaseUrl().$path;
        $startedAt = microtime(true);
        $context = [
            'endpoint' => $path,
            'url' => $endpoint,
            'source' => 'external',
            'b2b_catalog_endpoint' => true,
        ];

        try {
            $response = Http::timeout((int) config('portal.erp_timeout', 15))
                ->acceptJson()
                ->withHeaders($this->portalB2BAuthHeaders())
                ->get($endpoint, array_filter($query, fn ($value): bool => $value !== null && $value !== ''));

            $contentType = (string) $response->header('Content-Type');
            $context = array_merge($context, [
                'status' => $response->status(),
                'content_type' => $contentType,
                'elapsed_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ]);

            if (!$response->successful() || !str_contains($contentType, 'application/json')) {
                Log::warning('Portal B2B catalog request rejected.', $context);
                $this->abortIfAuthenticatedSessionRejected($response->status());

                return null;
            }

            Log::info('Portal B2B catalog request succeeded.', $context);

            return $response->json();
        } catch (\Throwable $exception) {
            Log::warning('Portal B2B catalog request failed.', array_merge($context, [
                'error' => $exception->getMessage(),
                'elapsed_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ]));

            return null;
        }
    }

    private function requestB2B(string $path, array $query = []): ?array
    {
        $hasB2BToken = $this->portalB2BToken() !== '';

        $endpoint = $this->erpBaseUrl().$path;
        $startedAt = microtime(true);
        $context = [
            'endpoint' => $path,
            'url' => $endpoint,
            'source' => 'external',
            'b2b_endpoint' => true,
            'b2b_token_present' => $hasB2BToken,
        ];

        try {
            $response = Http::timeout((int) config('portal.erp_timeout', 15))
                ->acceptJson()
                ->withHeaders($this->portalB2BAuthHeaders())
                ->get($endpoint, array_filter($query, fn ($value): bool => $value !== null && $value !== ''));

            $contentType = (string) $response->header('Content-Type');
            $context = array_merge($context, [
                'status' => $response->status(),
                'content_type' => $contentType,
                'elapsed_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ]);

            if (!$response->successful() || !str_contains($contentType, 'application/json')) {
                Log::warning('Portal B2B request rejected.', $context);
                $this->abortIfAuthenticatedSessionRejected($response->status());

                return null;
            }

            Log::info('Portal B2B request succeeded.', $context);

            return $response->json();
        } catch (\Throwable $exception) {
            Log::warning('Portal B2B request failed.', array_merge($context, [
                'error' => $exception->getMessage(),
                'elapsed_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ]));

            return null;
        }
    }

    public function brands(array $filters = []): array
    {
        return $this->fallback->brands($filters);
    }

    public function orders(array $filters = []): array
    {
        $query = [
            'page' => max(1, (int) ($filters['page'] ?? 1)),
            'per_page' => $this->perPage($filters['per_page'] ?? 10),
            'status' => $this->activeFilterValue($filters['status'] ?? null, ['todos', 'all']),
            'query' => $this->activeFilterValue($filters['query'] ?? null),
            'from' => $this->activeFilterValue($filters['from'] ?? null),
            'to' => $this->activeFilterValue($filters['to'] ?? null),
        ];

        $cached = $this->cachedOrdersPayload($query);

        if ($cached) {
            return $this->normalizeB2BOrders($cached['payload'], $cached['source']);
        }

        $fallback = $this->fallback->orders($filters);
        $fallback['_source'] = 'mock-fallback';

        return $fallback;
    }

    private function cachedOrdersPayload(array $query): ?array
    {
        $scope = $this->portalB2BCacheScope();
        $clientConfigured = $scope !== '';
        $query = array_filter($query, fn ($value): bool => $value !== null && $value !== '');
        $cacheKey = 'portal.orders.'.sha1($this->erpBaseUrl().'|'.$this->b2bOrdersPath().'|'.$scope.'|'.json_encode($query));
        $lastCacheKey = $cacheKey.'.last';

        if (Cache::has($cacheKey)) {
            Log::info('Portal orders served from fresh cache.', ['source' => 'external-cache', 'b2b_endpoint' => true, 'b2b_session_present' => $clientConfigured]);

            return ['payload' => Cache::get($cacheKey), 'source' => 'external-cache'];
        }

        $payload = $this->requestB2B($this->b2bOrdersPath(), $query);

        if ($payload) {
            Cache::put($cacheKey, $payload, now()->addMinutes(self::ORDERS_CACHE_TTL_MINUTES));
            Cache::put($lastCacheKey, $payload, now()->addHours(2));
            Log::info('Portal orders served from ERP B2B API and cached.', ['source' => 'external', 'b2b_endpoint' => true, 'b2b_session_present' => $clientConfigured]);

            return ['payload' => $payload, 'source' => 'external'];
        }

        $lastPayload = Cache::get($lastCacheKey);

        if (is_array($lastPayload)) {
            Log::warning('Portal orders ERP B2B API failed; served stale cache.', ['source' => 'external-cache', 'b2b_endpoint' => true, 'b2b_session_present' => $clientConfigured]);

            return ['payload' => $lastPayload, 'source' => 'external-cache'];
        }

        Log::warning('Portal orders ERP B2B API failed; no cache available, using mock fallback.', ['source' => 'mock-fallback', 'b2b_endpoint' => true, 'b2b_session_present' => $clientConfigured]);

        return null;
    }

    private function cachedB2BPayload(string $path, array $query, string $prefix, int $ttlMinutes): ?array
    {
        $scope = $this->portalB2BCacheScope();
        $clientConfigured = $scope !== '';
        $query = array_filter($query, fn ($value): bool => $value !== null && $value !== '');
        $cacheKey = $prefix.'.'.sha1($this->erpBaseUrl().'|'.$path.'|'.$scope.'|'.json_encode($query));
        $lastCacheKey = $cacheKey.'.last';

        if (Cache::has($cacheKey)) {
            Log::info('Portal B2B served from fresh cache.', ['source' => 'external-cache', 'b2b_endpoint' => true, 'b2b_session_present' => $clientConfigured, 'endpoint' => $path]);

            return ['payload' => Cache::get($cacheKey), 'source' => 'external-cache'];
        }

        $payload = $this->requestB2B($path, $query);

        if ($payload) {
            Cache::put($cacheKey, $payload, now()->addMinutes($ttlMinutes));
            Cache::put($lastCacheKey, $payload, now()->addHours(2));

            return ['payload' => $payload, 'source' => 'external'];
        }

        $lastPayload = Cache::get($lastCacheKey);
        if (is_array($lastPayload)) {
            Log::warning('Portal B2B request failed; served stale cache.', ['source' => 'external-cache', 'b2b_endpoint' => true, 'b2b_session_present' => $clientConfigured, 'endpoint' => $path]);

            return ['payload' => $lastPayload, 'source' => 'external-cache'];
        }

        return null;
    }

    private function withSource(array $payload, string $source): array
    {
        $payload['_source'] = $source;

        return $payload;
    }

    public function account(array $filters = []): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $query = [
            'page' => $page,
            'per_page' => $this->perPage($filters['per_page'] ?? $filters['limit'] ?? 20),
            'limit' => $this->perPage($filters['limit'] ?? $filters['per_page'] ?? 20),
            'filter' => $this->activeFilterValue($filters['filter'] ?? null, ['todos', 'all']),
            'period' => $this->activeFilterValue($filters['period'] ?? null),
            'query' => $this->activeFilterValue($filters['query'] ?? null),
            'include_summary' => $this->includeFlagValue($filters['include_summary'] ?? ($page === 1 ? 1 : 0)),
            'include_filters' => $this->includeFlagValue($filters['include_filters'] ?? ($page === 1 ? 1 : 0)),
        ];
        $cached = $this->cachedB2BPayload($this->b2bAccountPath(), $query, 'portal.account', self::ACCOUNT_CACHE_TTL_MINUTES);

        return $cached
            ? $this->withSource($this->normalizeB2BAccount($cached['payload']), $cached['source'])
            : $this->fallback->account($filters);
    }

    public function invoices(array $filters = []): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $query = [
            'page' => $page,
            'per_page' => $this->perPage($filters['per_page'] ?? 20),
            'query' => $this->activeFilterValue($filters['query'] ?? null),
            'status' => $this->activeFilterValue($filters['status'] ?? null, ['todos', 'all']),
            'from' => $this->activeFilterValue($filters['from'] ?? null),
            'to' => $this->activeFilterValue($filters['to'] ?? null),
            'year' => $this->activeFilterValue($filters['year'] ?? null, ['todos', 'all']),
            'include_filters' => $this->includeFlagValue($filters['include_filters'] ?? ($page === 1 ? 1 : 0)),
        ];
        $cached = $this->cachedB2BPayload($this->b2bInvoicesPath(), $query, 'portal.invoices', self::INVOICES_CACHE_TTL_MINUTES);

        return $cached ? $this->withSource($this->normalizeB2BInvoices($cached['payload']), $cached['source']) : $this->fallback->invoices($filters);
    }

    public function quotes(array $filters = []): array
    {
        $query = [
            'page' => max(1, (int) ($filters['page'] ?? 1)),
            'per_page' => $this->perPage($filters['per_page'] ?? 20),
            'search' => $this->activeFilterValue($filters['search'] ?? null),
            'status' => $this->activeFilterValue($filters['status'] ?? null, ['todos', 'all']),
            'archived' => $this->activeFilterValue($filters['archived'] ?? null),
        ];

        $cached = $this->cachedQuotesPayload($query);

        if ($cached) {
            return $this->normalizeB2BQuotes($cached['payload'], $cached['source']);
        }

        $fallback = $this->fallback->quotes($filters);
        $fallback['_source'] = 'mock-fallback';

        return $fallback;
    }

    private function cachedQuotesPayload(array $query): ?array
    {
        $scope = $this->portalB2BCacheScope();
        $clientConfigured = $scope !== '';
        $query = array_filter($query, fn ($value): bool => $value !== null && $value !== '');
        $cacheKey = 'portal.quotes.'.sha1($this->erpBaseUrl().'|'.$this->b2bQuotesPath().'|'.$scope.'|'.json_encode($query));
        $lastCacheKey = $cacheKey.'.last';

        if (Cache::has($cacheKey)) {
            Log::info('Portal quotes served from fresh cache.', ['source' => 'external-cache', 'b2b_endpoint' => true, 'b2b_session_present' => $clientConfigured]);

            return ['payload' => Cache::get($cacheKey), 'source' => 'external-cache'];
        }

        $payload = $this->requestB2B($this->b2bQuotesPath(), $query);

        if ($payload) {
            Cache::put($cacheKey, $payload, now()->addMinutes(self::QUOTES_CACHE_TTL_MINUTES));
            Cache::put($lastCacheKey, $payload, now()->addHours(2));
            Log::info('Portal quotes served from ERP B2B API and cached.', ['source' => 'external', 'b2b_endpoint' => true, 'b2b_session_present' => $clientConfigured]);

            return ['payload' => $payload, 'source' => 'external'];
        }

        $lastPayload = Cache::get($lastCacheKey);

        if (is_array($lastPayload)) {
            Log::warning('Portal quotes ERP B2B API failed; served stale cache.', ['source' => 'external-cache', 'b2b_endpoint' => true, 'b2b_session_present' => $clientConfigured]);

            return ['payload' => $lastPayload, 'source' => 'external-cache'];
        }

        Log::warning('Portal quotes ERP B2B API failed; no cache available, using mock fallback.', ['source' => 'mock-fallback', 'b2b_endpoint' => true, 'b2b_session_present' => $clientConfigured]);

        return null;
    }

    public function profile(array $filters = []): array
    {
        $payload = $this->requestB2B($this->b2bProfilePath());

        return $payload ? $this->normalizeB2BProfile($payload) : $this->fallback->profile($filters);
    }

    private function requestExternal(string $path, array $query = []): ?array
    {
        $endpoint = $this->erpBaseUrl().$path;
        $auth = $this->erpAuthHeaders();
        $cookieHeader = $auth['cookie'];
        $xsrfToken = $auth['xsrf'];
        $context = [
            'endpoint' => $path,
            'url' => $endpoint,
            'cookie_present' => $cookieHeader !== '',
            'xsrf_present' => $xsrfToken !== '',
        ];
        $startedAt = microtime(true);

        try {
            $response = $this->erpRequest($endpoint, $query, $cookieHeader, $xsrfToken);

            if ($response->status() === 401 && $this->canAutoLogin() && !$this->hasManualCookie()) {
                $this->forgetCachedErpAuth();
                $auth = $this->erpAuthHeaders(forceLogin: true);
                $cookieHeader = $auth['cookie'];
                $xsrfToken = $auth['xsrf'];
                $context['cookie_present'] = $cookieHeader !== '';
                $context['xsrf_present'] = $xsrfToken !== '';
                $response = $this->erpRequest($endpoint, $query, $cookieHeader, $xsrfToken);
            }

            $contentType = (string) $response->header('Content-Type');
            $context = array_merge($context, [
                'status' => $response->status(),
                'content_type' => $contentType,
                'elapsed_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ]);

            if (!$response->successful() || !str_contains($contentType, 'application/json')) {
                Log::warning('Portal ERP request rejected.', $context);

                return null;
            }

            Log::info('Portal ERP request succeeded.', $context);

            return $response->json();
        } catch (\Throwable $exception) {
            Log::warning('Portal ERP request failed.', array_merge($context, [
                'error' => $exception->getMessage(),
                'elapsed_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ]));

            return null;
        }
    }

    private function portalB2BToken(): string
    {
        return (string) ($this->portalSessionPayload()['fastevo_b2b_token'] ?? '');
    }

    private function portalB2BCacheScope(): string
    {
        $payload = $this->portalSessionPayload();
        $token = (string) ($payload['fastevo_b2b_token'] ?? '');

        if ($token !== '') {
            return 'session:'.sha1($token);
        }

        $devClientId = trim((string) config('portal.erp_b2b_client_id', ''));

        return $devClientId === '' ? '' : 'dev:'.sha1($devClientId);
    }

    private function portalB2BAuthHeaders(): array
    {
        $token = $this->portalB2BToken();

        return $token === '' ? [] : ['Authorization' => 'Bearer '.$token];
    }

    private function portalSessionPayload(): array
    {
        $token = trim((string) request()->header('X-Portal-Session', ''));

        if ($token === '') {
            return [];
        }

        try {
            $payload = json_decode(Crypt::decryptString($token), true, 512, JSON_THROW_ON_ERROR);
            $b2bToken = trim((string) ($payload['fastevo_b2b_token'] ?? ''));
            $expiresAt = trim((string) ($payload['expires_at'] ?? ''));
            $expired = $expiresAt === '' || now()->greaterThan(\Carbon\Carbon::parse($expiresAt));

            if ($b2bToken === '' || $expired) {
                Log::warning('Portal session token rejected.', [
                    'token_present' => true,
                    'token_valid' => false,
                    'b2b_token_present' => $b2bToken !== '',
                    'expired' => $expired,
                ]);

                $this->abortPortalSessionExpired();
            }

            Log::info('Portal session token accepted.', [
                'token_present' => true,
                'token_valid' => true,
                'b2b_token_present' => true,
            ]);

            return $payload;
        } catch (\Throwable $exception) {
            Log::warning('Portal session token invalid.', [
                'token_present' => true,
                'token_valid' => false,
                'error' => $exception->getMessage(),
            ]);

            $this->abortPortalSessionExpired();
        }
    }

    private function abortIfAuthenticatedSessionRejected(int $status): void
    {
        if ($status === 401 && trim((string) request()->header('X-Portal-Session', '')) !== '') {
            $this->abortPortalSessionExpired();
        }
    }

    private function abortPortalSessionExpired(): never
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Sesion B2B expirada o invalida.',
            'meta' => ['source' => 'fastevo-public-b2b', 'session_expired' => true],
        ], 401));
    }

    private function erpRequest(string $endpoint, array $query, string $cookieHeader, string $xsrfToken)
    {
        return Http::timeout((int) config('portal.erp_timeout', 15))
            ->acceptJson()
            ->withHeaders(array_filter([
                'X-Requested-With' => 'XMLHttpRequest',
                'X-XSRF-TOKEN' => $xsrfToken,
                'Cookie' => $cookieHeader,
            ]))
            ->get($endpoint, array_filter($query, fn ($value): bool => $value !== null && $value !== ''));
    }

    private function erpAuthHeaders(bool $forceLogin = false): array
    {
        $manualCookie = trim((string) config('portal.erp_cookie', ''));

        if ($manualCookie !== '') {
            return [
                'cookie' => $manualCookie,
                'xsrf' => trim((string) config('portal.erp_xsrf_token', '')),
            ];
        }

        if ($this->canAutoLogin()) {
            $auth = $this->cachedErpLogin($forceLogin);

            if (($auth['cookie'] ?? '') !== '') {
                return $auth;
            }
        }

        return [
            'cookie' => $this->requestCookieHeader(),
            'xsrf' => (string) request()->cookies->get('XSRF-TOKEN', ''),
        ];
    }

    private function cachedErpLogin(bool $force = false): array
    {
        $cacheKey = $this->erpAuthCacheKey();

        if (!$force) {
            $cached = Cache::get($cacheKey);

            if (is_array($cached) && ($cached['cookie'] ?? '') !== '') {
                return $cached;
            }
        }

        $auth = $this->loginToErp();

        if (($auth['cookie'] ?? '') !== '') {
            Cache::put($cacheKey, $auth, now()->addMinutes(25));
        }

        return $auth;
    }

    private function loginToErp(): array
    {
        $loginPath = '/'.ltrim((string) config('portal.erp_login_url', '/login'), '/');
        $loginUrl = $this->erpBaseUrl().$loginPath;
        $timeout = (int) config('portal.erp_timeout', 15);

        Log::info('Portal ERP login attempt.', ['login_attempt' => true]);

        try {
            $loginPage = Http::timeout($timeout)->get($loginUrl);
            $initialCookie = $this->cookieHeaderFromResponse($loginPage);
            $csrfToken = $this->csrfTokenFromHtml((string) $loginPage->body());
            $xsrfToken = $this->cookieValue($initialCookie, 'XSRF-TOKEN') ?: $csrfToken;

            foreach (['email', 'username'] as $field) {
                $response = Http::timeout($timeout)
                    ->acceptJson()
                    ->asForm()
                    ->withHeaders(array_filter([
                        'X-Requested-With' => 'XMLHttpRequest',
                        'X-CSRF-TOKEN' => $csrfToken,
                        'X-XSRF-TOKEN' => $xsrfToken,
                        'Cookie' => $initialCookie,
                        'Referer' => $loginUrl,
                    ]))
                    ->post($loginUrl, array_filter([
                        '_token' => $csrfToken,
                        $field => (string) config('portal.erp_username', ''),
                        'password' => (string) config('portal.erp_password', ''),
                    ], fn ($value): bool => $value !== null && $value !== ''));

                $cookieHeader = $this->mergeCookieHeaders($initialCookie, $this->cookieHeaderFromResponse($response));

                $loginAccepted = $response->successful() || in_array($response->status(), [302, 303], true);

                if ($loginAccepted) {
                    if ($cookieHeader !== '') {
                        Log::info('Portal ERP login finished.', [
                            'login_success' => true,
                            'status' => $response->status(),
                            'cookie_present' => true,
                            'xsrf_present' => $xsrfToken !== '',
                        ]);

                        return ['cookie' => $cookieHeader, 'xsrf' => $xsrfToken];
                    }
                }
            }

            Log::warning('Portal ERP login failed.', [
                'login_success' => false,
                'status' => $loginPage->status(),
                'cookie_present' => $initialCookie !== '',
                'xsrf_present' => $xsrfToken !== '',
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Portal ERP login exception.', [
                'login_success' => false,
                'error' => $exception->getMessage(),
            ]);
        }

        return ['cookie' => '', 'xsrf' => ''];
    }

    private function canAutoLogin(): bool
    {
        return trim((string) config('portal.erp_username', '')) !== ''
            && trim((string) config('portal.erp_password', '')) !== '';
    }

    private function hasManualCookie(): bool
    {
        return trim((string) config('portal.erp_cookie', '')) !== '';
    }

    private function forgetCachedErpAuth(): void
    {
        Cache::forget($this->erpAuthCacheKey());
    }

    private function erpAuthCacheKey(): string
    {
        return 'portal.erp.auth.'.sha1((string) config('portal.erp_base_url', '').'|'.(string) config('portal.erp_username', ''));
    }

    private function cookieHeaderFromResponse($response): string
    {
        $cookies = [];
        $headers = $response->headers();
        $setCookies = $headers['Set-Cookie'] ?? $headers['set-cookie'] ?? [];
        $setCookies = is_array($setCookies) ? $setCookies : [$setCookies];

        foreach ($setCookies as $cookie) {
            $pair = trim(explode(';', (string) $cookie)[0] ?? '');

            if ($pair !== '') {
                $cookies[] = $pair;
            }
        }

        return implode('; ', array_unique($cookies));
    }

    private function mergeCookieHeaders(string ...$headers): string
    {
        $cookies = [];

        foreach ($headers as $header) {
            foreach (array_filter(array_map('trim', explode(';', $header))) as $pair) {
                $name = explode('=', $pair, 2)[0] ?? '';

                if ($name !== '') {
                    $cookies[$name] = $pair;
                }
            }
        }

        return implode('; ', array_values($cookies));
    }

    private function csrfTokenFromHtml(string $html): string
    {
        return preg_match('/<meta\s+name=["\']csrf-token["\']\s+content=["\']([^"\']+)["\']/i', $html, $matches)
            ? (string) $matches[1]
            : '';
    }

    private function cookieValue(string $cookieHeader, string $name): string
    {
        foreach (array_filter(array_map('trim', explode(';', $cookieHeader))) as $pair) {
            [$cookieName, $value] = array_pad(explode('=', $pair, 2), 2, '');

            if ($cookieName === $name) {
                return urldecode($value);
            }
        }

        return '';
    }

    private function erpCookieHeader(): string
    {
        return $this->erpAuthHeaders()['cookie'];
    }

    private function erpXsrfToken(): string
    {
        return $this->erpAuthHeaders()['xsrf'];
    }

    private function requestCookieHeader(): string
    {
        $cookies = request()->cookies;
        $forward = [];

        foreach (['fastbi_session', 'laravel_session', 'XSRF-TOKEN'] as $name) {
            $value = $cookies->get($name);

            if ($value) {
                $forward[] = $name.'='.$value;
            }
        }

        return implode('; ', $forward);
    }

    private function normalizeCatalog(array $payload, array $filters = [], string $source = 'external'): array
    {
        $isB2BPayload = isset($payload['data']) && is_array($payload['data']);
        $items = array_map(
            fn (array $item): array => $isB2BPayload ? $this->normalizeB2BProduct($item) : $this->normalizeProduct($item),
            $isB2BPayload ? ($payload['data']['products'] ?? []) : ($payload['items'] ?? [])
        );
        $pagination = $isB2BPayload ? ($payload['meta'] ?? []) : ($payload['pagination'] ?? []);
        $perPage = (int) ($pagination['per_page'] ?? $filters['per_page'] ?? max(1, count($items)));
        $total = (int) ($pagination['total'] ?? count($items));
        $currentPage = (int) ($pagination['current_page'] ?? $filters['page'] ?? 1);

        $catalog = [
            '_source' => $source,
            '_meta' => [
                'current_page' => max(1, $currentPage),
                'page' => max(1, $currentPage),
                'per_page' => max(1, $perPage),
                'total' => max(0, $total),
                'last_page' => (int) max(1, ceil(max(0, $total) / max(1, $perPage))),
            ],
            'products' => $items,
        ];

        if ($this->shouldIncludeCatalogFilters($filters)) {
            $catalog['filters'] = $isB2BPayload && isset($payload['data']['filters']) && is_array($payload['data']['filters'])
                ? $payload['data']['filters']
                : $this->catalogFilters($items);
        }

        return $catalog;
    }

    private function shouldIncludeCatalogFilters(array $filters): bool
    {
        return !in_array((string) ($filters['include_filters'] ?? '1'), ['0', 'false', 'no'], true);
    }

    private function activeFilterValue(mixed $value, array $inactiveValues = []): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        $text = strtolower(trim((string) $value));

        return in_array($text, $inactiveValues, true) ? null : $value;
    }

    private function includeFiltersValue(mixed $value): int
    {
        return in_array(strtolower(trim((string) ($value ?? '1'))), ['0', 'false', 'no'], true) ? 0 : 1;
    }

    private function includeFlagValue(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return in_array(strtolower(trim((string) $value)), ['0', 'false', 'no'], true) ? 0 : 1;
    }

    private function catalogFilters(array $pageItems): array
    {
        $cacheKey = 'portal.catalog.filters.external.'.sha1($this->cacheScope()).'.v1';

        try {
            $filters = Cache::get($cacheKey);

            if (!is_array($filters)) {
                Log::info('Portal catalog filters warmup started.', ['per_page' => self::FILTER_WARMUP_PER_PAGE, 'pages_per_cycle' => self::FILTER_WARMUP_PAGES_PER_CYCLE]);
                $filters = $this->buildCatalogFilters($cacheKey, $this->filtersFromProducts($pageItems));

                if (count($filters['categories'] ?? []) > 0 || count($filters['brands'] ?? []) > 0) {
                    Cache::put($cacheKey, $filters, now()->addMinutes(self::CATALOG_CACHE_TTL_MINUTES));
                    Cache::put($cacheKey.'.last', $filters, now()->addHours(6));
                    Log::info('Portal catalog filters warmup completed.', [
                        'categories' => count($filters['categories'] ?? []),
                        'brands' => count($filters['brands'] ?? []),
                        'warming' => (bool) ($filters['warming'] ?? false),
                    ]);
                } else {
                    $lastFilters = Cache::get($cacheKey.'.last');

                    if (is_array($lastFilters)) {
                        $filters = $lastFilters;
                        Log::warning('Portal catalog filters ERP returned empty; served stale cache.');
                    }
                }
            } elseif ((bool) ($filters['warming'] ?? false)) {
                Log::info('Portal catalog filters warmup started.', ['reason' => 'continue_warming', 'per_page' => self::FILTER_WARMUP_PER_PAGE, 'pages_per_cycle' => self::FILTER_WARMUP_PAGES_PER_CYCLE]);
                $filters = $this->buildCatalogFilters($cacheKey, $filters);
                Cache::put($cacheKey, $filters, now()->addMinutes(self::CATALOG_CACHE_TTL_MINUTES));
                Cache::put($cacheKey.'.last', $filters, now()->addHours(6));
                Log::info('Portal catalog filters warmup completed.', [
                    'categories' => count($filters['categories'] ?? []),
                    'brands' => count($filters['brands'] ?? []),
                    'warming' => (bool) ($filters['warming'] ?? false),
                ]);
            } else {
                Log::info('Portal catalog filters warmup skipped.', ['reason' => 'fresh_complete_cache']);
            }
        } catch (\Throwable) {
            $filters = Cache::get($cacheKey.'.last');

            if (is_array($filters)) {
                Log::warning('Portal catalog filters failed; served stale cache.');
            } else {
                Log::warning('Portal catalog filters failed; using current page fallback.');
                $filters = $this->filtersFromProducts($pageItems);
            }
        }

        return count($filters['categories'] ?? []) > 0 || count($filters['brands'] ?? []) > 0
            ? $filters
            : $this->filtersFromProducts($pageItems);
    }

    private function buildCatalogFilters(string $cacheKey, array $seedFilters): array
    {
        $lastFilters = Cache::get($cacheKey.'.last');
        $filters = is_array($lastFilters) ? $this->mergeCatalogFilters($lastFilters, $seedFilters) : $seedFilters;
        $page = max(1, (int) Cache::get($cacheKey.'.cursor', 1));
        $lastPage = $page;
        $processed = 0;

        do {
            $payload = $this->requestB2BCatalog([
                'page' => $page,
                'per_page' => self::FILTER_WARMUP_PER_PAGE,
            ]);

            if (!$payload) {
                break;
            }

            $items = $payload['data']['products'] ?? [];
            $filters = $this->mergeCatalogFilters($filters, $payload['data']['filters'] ?? $this->filtersFromProducts($items));

            $pagination = $payload['meta'] ?? [];
            $lastPage = (int) ($pagination['last_page'] ?? 0);
            $total = (int) ($pagination['total'] ?? 0);
            $responsePerPage = (int) ($pagination['per_page'] ?? count($items));
            $lastPage = $lastPage > 0 ? $lastPage : (int) max(1, ceil($total / max(1, $responsePerPage)));

            $page += 1;
            $processed += 1;
        } while ($page <= $lastPage && count($items) > 0 && $processed < self::FILTER_WARMUP_PAGES_PER_CYCLE);

        $filters['warming'] = $page <= $lastPage;
        $filters['warmup_page'] = min($page, max(1, $lastPage));
        $filters['warmup_last_page'] = max(1, $lastPage);
        $filters['warmup_processed_pages'] = $processed;
        Cache::put($cacheKey.'.cursor', $filters['warming'] ? $page : 1, now()->addHours(6));

        return $filters;
    }

    private function filtersFromRawProducts(array $items): array
    {
        $categories = [];
        $brands = [];
        $prices = [];
        $availability = ['all'];

        foreach ($items as $item) {
            $category = $this->textValue($item['categories.name'] ?? 'General');
            $brand = $this->textValue($item['brands.name'] ?? 'General');
            $price = $this->numberValue($item['company_account_products.price'] ?? $item['company_account_products.base_price'] ?? 0);
            $qty = $this->nullableNumber($item['company_account_products.qty'] ?? null);

            if ($category !== '') {
                $categories[] = $category;
            }

            if ($brand !== '') {
                $brands[] = $brand;
            }

            if ($price > 0) {
                $prices[] = $price;
            }

            if ($qty !== null && $qty > 0) {
                $availability[] = 'available';
            }

            if ($qty === null || $qty <= 0) {
                $availability[] = 'unavailable';
            }
        }

        return [
            'categories' => array_values(array_unique($categories)),
            'brands' => array_values(array_unique($brands)),
            'min_price' => count($prices) > 0 ? min($prices) : 0,
            'max_price' => count($prices) > 0 ? max($prices) : 0,
            'availability' => array_values(array_unique($availability)),
        ];
    }

    private function mergeCatalogFilters(array $base, array $next): array
    {
        $baseMin = (float) ($base['min_price'] ?? 0);
        $nextMin = (float) ($next['min_price'] ?? 0);

        return [
            'categories' => array_values(array_unique(array_merge($base['categories'] ?? [], $next['categories'] ?? []))),
            'brands' => array_values(array_unique(array_merge($base['brands'] ?? [], $next['brands'] ?? []))),
            'min_price' => $baseMin > 0 && $nextMin > 0 ? min($baseMin, $nextMin) : max($baseMin, $nextMin),
            'max_price' => max((float) ($base['max_price'] ?? 0), (float) ($next['max_price'] ?? 0)),
            'availability' => array_values(array_unique(array_merge($base['availability'] ?? ['all'], $next['availability'] ?? ['all']))),
            'warming' => (bool) ($next['warming'] ?? $base['warming'] ?? false),
            'warmup_page' => (int) ($next['warmup_page'] ?? $base['warmup_page'] ?? 1),
            'warmup_last_page' => (int) ($next['warmup_last_page'] ?? $base['warmup_last_page'] ?? 1),
        ];
    }

    private function filtersFromProducts(array $items): array
    {
        $prices = array_map(fn (array $item): float => (float) ($item['priceValue'] ?? 0), $items);

        return [
            'categories' => array_values(array_unique(array_filter(array_column($items, 'category')))),
            'brands' => array_values(array_unique(array_filter(array_column($items, 'brand')))),
            'min_price' => count($prices) > 0 ? min($prices) : 0,
            'max_price' => count($prices) > 0 ? max($prices) : 0,
            'availability' => ['all', 'available', 'unavailable'],
        ];
    }

    private function perPage(mixed $value): int
    {
        $requested = (int) $value;
        $capped = max(1, min(100, $requested));

        if ($requested > 100) {
            Log::warning('Portal catalog per_page capped.', ['requested' => $requested, 'capped' => $capped]);
        }

        return $capped;
    }

    private function erpBaseUrl(): string
    {
        return rtrim((string) config('portal.erp_base_url', 'http://localhost:8001'), '/');
    }

    private function b2bProductsPath(): string
    {
        return '/'.ltrim((string) config('portal.erp_b2b_products_path', '/api/portal-b2b/products'), '/');
    }

    private function b2bInvoicesPath(): string
    {
        return '/'.ltrim((string) config('portal.erp_b2b_invoices_path', '/api/portal-b2b/invoices'), '/');
    }

    private function b2bAccountPath(): string
    {
        return '/'.ltrim((string) config('portal.erp_b2b_account_path', '/api/portal-b2b/account'), '/');
    }

    private function b2bProfilePath(): string
    {
        return '/'.ltrim((string) config('portal.erp_b2b_profile_path', '/api/portal-b2b/profile'), '/');
    }

    private function b2bDashboardPath(): string
    {
        return '/'.ltrim((string) config('portal.erp_b2b_dashboard_path', '/api/portal-b2b/dashboard'), '/');
    }

    private function b2bOrdersPath(): string
    {
        return '/'.ltrim((string) config('portal.erp_b2b_orders_path', '/api/portal-b2b/orders'), '/');
    }

    private function b2bQuotesPath(): string
    {
        return '/'.ltrim((string) config('portal.erp_b2b_quotes_path', '/api/portal-b2b/quotes'), '/');
    }

    private function b2bCatalogCacheScope(): string
    {
        return sha1($this->erpBaseUrl().'|'.$this->b2bProductsPath());
    }

    private function cacheScope(): string
    {
        return sha1($this->erpCookieHeader() ?: 'anonymous');
    }

    private function normalizeProduct(array $item): array
    {
        $sku = $this->textValue($item['product_key'] ?? null);
        $name = trim((string) ($item['notes'] ?? $sku));
        $priceValue = $this->numberValue($item['company_account_products.price'] ?? $item['company_account_products.base_price'] ?? 0);
        $qty = $this->nullableNumber($item['company_account_products.qty'] ?? null);
        $hasQty = $qty !== null;

        return [
            'id' => $sku !== '' ? $sku : $this->textValue($item['id'] ?? ''),
            'sku' => $sku,
            'name' => $name,
            'description' => $name,
            'brand' => $this->textValue($item['brands.name'] ?? 'General'),
            'category' => $this->textValue($item['categories.name'] ?? 'General'),
            'price' => $this->moneyLabel($priceValue),
            'priceValue' => $priceValue,
            'price_value' => $priceValue,
            'stock' => $qty,
            'availableQty' => $qty,
            'available_qty' => $qty,
            'isAvailable' => $hasQty && $qty > 0,
            'is_available' => $hasQty && $qty > 0,
            'stockLabel' => $hasQty ? ($qty > 0 ? 'Disponible: '.(int) $qty.' unidades' : 'Consultar disponibilidad') : 'Consultar disponibilidad',
            'stock_label' => $hasQty ? ($qty > 0 ? 'Disponible: '.(int) $qty.' unidades' : 'Consultar disponibilidad') : 'Consultar disponibilidad',
            'lastStockUpdate' => $this->textValue($item['company_account_products.last_stock_update'] ?? ''),
            'last_stock_update' => $this->textValue($item['company_account_products.last_stock_update'] ?? ''),
            'image' => $this->normalizeImage($item['picture'] ?? ''),
        ];
    }

    private function normalizeB2BProduct(array $item): array
    {
        $priceValue = $this->numberValue($item['priceValue'] ?? $item['price_value'] ?? $item['price'] ?? 0);
        $qty = $this->nullableNumber($item['availableQty'] ?? $item['available_qty'] ?? $item['stock'] ?? null);
        $hasQty = $qty !== null;
        $isAvailable = $item['isAvailable'] ?? $item['is_available'] ?? ($hasQty && $qty > 0);

        return [
            'id' => $item['id'] ?? $this->textValue($item['sku'] ?? ''),
            'sku' => $this->textValue($item['sku'] ?? ''),
            'name' => $this->textValue($item['name'] ?? $item['sku'] ?? ''),
            'description' => $this->textValue($item['description'] ?? $item['name'] ?? ''),
            'brand' => $this->textValue($item['brand'] ?? 'General'),
            'category' => $this->textValue($item['category'] ?? 'General'),
            'price' => $this->textValue($item['price'] ?? '') !== '' ? $this->textValue($item['price']) : $this->moneyLabel($priceValue),
            'priceValue' => $priceValue,
            'price_value' => $priceValue,
            'stock' => $qty,
            'availableQty' => $qty,
            'available_qty' => $qty,
            'isAvailable' => (bool) $isAvailable,
            'is_available' => (bool) $isAvailable,
            'stockLabel' => $this->textValue($item['stockLabel'] ?? $item['stock_label'] ?? ($hasQty && $qty > 0 ? 'Disponible: '.(int) $qty.' unidades' : 'Consultar disponibilidad')),
            'stock_label' => $this->textValue($item['stockLabel'] ?? $item['stock_label'] ?? ($hasQty && $qty > 0 ? 'Disponible: '.(int) $qty.' unidades' : 'Consultar disponibilidad')),
            'lastStockUpdate' => $this->textValue($item['lastStockUpdate'] ?? $item['last_stock_update'] ?? ''),
            'last_stock_update' => $this->textValue($item['lastStockUpdate'] ?? $item['last_stock_update'] ?? ''),
            'image' => $this->normalizeImage($item['image'] ?? ''),
        ];
    }

    private function normalizeAccount(array $receivablesPayload, array $paymentsPayload): array
    {
        $receivables = array_map(fn (array $item): array => $this->normalizeReceivable($item), $receivablesPayload['items'] ?? []);
        $payments = array_map(fn (array $item): array => $this->normalizePayment($item), $paymentsPayload['items'] ?? []);
        $pendingTotal = array_sum(array_map(fn (array $item): float => $this->numberValue($item['amount'] ?? 0), $receivables));
        $lastPayment = $payments[0] ?? null;

        return [
            '_source' => 'external',
            'overview' => [
                'debt_total' => $this->moneyLabel($pendingTotal),
                'credit_available' => 'Consultar',
                'credit_trend' => 'Datos ERP',
                'credit_used' => 'Consultar',
                'last_payment' => $lastPayment['amount'] ?? 'L. 0.00',
                'last_payment_date' => $lastPayment['date'] ?? '',
                'next_due' => $receivables[0]['date'] ?? '',
                'next_due_text' => 'Segun cuentas por cobrar ERP',
            ],
            'credit_usage' => [],
            'chart' => ['months' => [], 'bars' => []],
            'movements' => array_values(array_merge($receivables, $payments)),
            'filters' => ['Todos', 'PENDIENTE', 'PAGADO', 'APLICADO'],
            'periods' => array_values(array_unique(array_filter(array_map(fn (array $item): string => substr((string) ($item['date'] ?? ''), 0, 4), array_merge($receivables, $payments))))),
        ];
    }

    private function normalizeB2BAccount(array $payload): array
    {
        $data = $payload['data'] ?? [];
        $movements = is_array($data['movements'] ?? null) ? $data['movements'] : [];
        $payments = is_array($data['payments'] ?? null) ? $data['payments'] : [];

        return [
            '_source' => 'external',
            '_meta' => is_array($payload['meta'] ?? null) ? $payload['meta'] : [],
            'overview' => is_array($data['overview'] ?? null) ? $data['overview'] : [],
            'credit_usage' => is_array($data['credit_usage'] ?? null) ? $data['credit_usage'] : [],
            'chart' => is_array($data['chart'] ?? null) ? $data['chart'] : ['months' => [], 'bars' => []],
            'movements' => array_values($movements),
            'payments' => $payments,
            'filters' => is_array($data['filters'] ?? null) ? $data['filters'] : ['Todos', 'Pendiente', 'Pagado', 'Vencido', 'Aplicado'],
            'periods' => is_array($data['periods'] ?? null) ? $data['periods'] : [],
            'dateRange' => is_array($data['dateRange'] ?? null) ? $data['dateRange'] : ['min' => '', 'max' => ''],
        ];
    }

    private function normalizeReceivable(array $item): array
    {
        return [
            'id' => $this->textValue($item['receivable_number'] ?? ''),
            'date' => $this->textValue($item['receivable_date'] ?? ''),
            'title' => $this->textValue($item['clients.name'] ?? 'Cuenta por cobrar'),
            'type' => $this->textValue($item['payment_types.name'] ?? 'CxC'),
            'amount' => $this->moneyLabel($this->numberValue($item['amount'] ?? 0)),
            'status' => $this->textValue($item['is_complete']['text'] ?? $item['is_complete'] ?? 'PENDIENTE'),
        ];
    }

    private function normalizePayment(array $item): array
    {
        return [
            'id' => $this->textValue($item['receivable_number'] ?? ''),
            'date' => $this->textValue($item['receivable_date'] ?? ''),
            'title' => 'Pago recibido - '.$this->textValue($item['clients.name'] ?? ''),
            'type' => $this->textValue($item['payment_types.name'] ?? 'Pago'),
            'amount' => '+'.$this->moneyLabel($this->numberValue($item['amount'] ?? 0)),
            'status' => $this->textValue($item['check_status']['text'] ?? $item['check_status'] ?? 'APLICADO'),
        ];
    }

    private function normalizeInvoices(array $payload): array
    {
        $invoices = array_map(fn (array $item): array => $this->normalizeInvoice($item), $payload['items'] ?? []);
        $pendingTotal = array_sum(array_map(fn (array $item): float => ($item['status'] ?? '') === 'Pendiente' ? $this->numberValue($item['balance'] ?? $item['total'] ?? 0) : 0, $invoices));
        $paidCount = count(array_filter($invoices, fn (array $item): bool => ($item['status'] ?? '') === 'Pagada'));

        return [
            '_source' => 'external',
            'summary' => [
                ['icon' => 'receipt_long', 'label' => 'Facturas ERP', 'value' => (string) ($payload['pagination']['total'] ?? count($invoices)), 'trend' => 'Lectura ERP', 'tone' => 'cyan'],
                ['icon' => 'pending_actions', 'label' => 'Pendiente', 'value' => $this->moneyLabel($pendingTotal), 'trend' => 'Saldo abierto', 'tone' => 'amber'],
                ['icon' => 'verified', 'label' => 'Pagadas', 'value' => (string) $paidCount, 'trend' => 'Pagina actual', 'tone' => 'emerald'],
            ],
            'invoices' => $invoices,
        ];
    }

    private function normalizeB2BInvoices(array $payload): array
    {
        $data = $payload['data'] ?? [];

        return [
            '_source' => 'external',
            '_meta' => is_array($payload['meta'] ?? null) ? $payload['meta'] : [],
            'summary' => is_array($data['summary'] ?? null) ? $data['summary'] : [],
            'statuses' => is_array($data['statuses'] ?? null) ? $data['statuses'] : ['Todos', 'Pendiente', 'Convertida', 'Pagada', 'Anulada'],
            'filters' => is_array($data['filters'] ?? null) ? $data['filters'] : [],
            'invoices' => is_array($data['invoices'] ?? null) ? $data['invoices'] : [],
        ];
    }

    private function normalizeB2BProfile(array $payload): array
    {
        $data = $payload['data'] ?? [];

        return [
            '_source' => 'external',
            '_meta' => is_array($payload['meta'] ?? null) ? $payload['meta'] : [],
            'user' => is_array($data['user'] ?? null) ? $data['user'] : null,
            'company' => is_array($data['company'] ?? null) ? $data['company'] : null,
            'commercial' => is_array($data['commercial'] ?? null) ? $data['commercial'] : null,
            'activity' => is_array($data['activity'] ?? null) ? $data['activity'] : [],
        ];
    }

    private function normalizeB2BDashboard(array $payload, string $source = 'external'): array
    {
        $data = $payload['data'] ?? [];

        return [
            '_source' => $source,
            '_meta' => is_array($payload['meta'] ?? null) ? $payload['meta'] : [],
            'profile' => is_array($data['profile'] ?? null) ? $data['profile'] : null,
            'overview' => is_array($data['overview'] ?? null) ? $data['overview'] : null,
            'kpis' => is_array($data['kpis'] ?? null) ? $data['kpis'] : [],
            'recent_activity' => is_array($data['recent_activity'] ?? null) ? $data['recent_activity'] : [],
            'chart' => is_array($data['chart'] ?? null) ? $data['chart'] : ['type' => 'monthly_cashflow', 'months' => [], 'invoices' => [], 'payments' => []],
            'quick_actions' => is_array($data['quick_actions'] ?? null) ? $data['quick_actions'] : [],
            'promotions' => is_array($data['promotions'] ?? null) ? $data['promotions'] : [],
            'featured_products' => is_array($data['featured_products'] ?? null) ? $data['featured_products'] : [],
        ];
    }

    private function normalizeB2BOrders(array $payload, string $source = 'external'): array
    {
        $data = $payload['data'] ?? [];

        return [
            '_source' => $source,
            '_meta' => is_array($payload['meta'] ?? null) ? $payload['meta'] : [],
            'orders' => is_array($data['orders'] ?? null) ? $data['orders'] : [],
            'active_order' => is_array($data['active_order'] ?? null) ? $data['active_order'] : null,
        ];
    }

    private function normalizeB2BQuotes(array $payload, string $source = 'external'): array
    {
        $data = $payload['data'] ?? [];

        return [
            '_source' => $source,
            '_meta' => is_array($payload['meta'] ?? null) ? $payload['meta'] : [],
            'stats' => is_array($data['stats'] ?? null) ? $data['stats'] : [],
            'quotes' => is_array($data['quotes'] ?? null) ? $data['quotes'] : [],
        ];
    }

    private function normalizeInvoice(array $item): array
    {
        $balance = $this->numberValue($item['balance'] ?? 0);
        $status = $balance > 0 ? 'Pendiente' : 'Pagada';

        return [
            'number' => $this->textValue($item['invoice_number'] ?? ''),
            'client' => $this->textValue($item['clients.name'] ?? ''),
            'seller' => $this->textValue($item['users.first_name'] ?? ''),
            'date' => $this->textValue($item['invoice_date'] ?? ''),
            'due' => $this->textValue($item['invoice_date'] ?? ''),
            'status' => $status,
            'tone' => $status === 'Pagada' ? 'paid' : 'pending',
            'total' => $this->moneyLabel($this->numberValue($item['amount'] ?? 0)),
            'balance' => $this->moneyLabel($balance),
        ];
    }

    private function textValue(mixed $value): string
    {
        if (is_array($value)) {
            return trim((string) ($value['text'] ?? ''));
        }

        return trim((string) $value);
    }

    private function nullableNumber(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $this->numberValue($value);
    }

    private function numberValue(mixed $value): float
    {
        return (float) str_replace([',', '$', 'L.'], '', $this->textValue($value));
    }

    private function moneyLabel(float $value): string
    {
        return 'L. '.number_format($value, 2, '.', ',');
    }

    private function normalizeImage(mixed $value): string
    {
        $image = $this->textValue($value);

        if ($image === '') {
            return '';
        }

        return str_starts_with($image, 'http') ? $image : $this->erpBaseUrl().'/'.ltrim($image, '/');
    }
}
