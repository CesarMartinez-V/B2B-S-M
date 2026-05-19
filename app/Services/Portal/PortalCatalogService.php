<?php

namespace App\Services\Portal;

use App\Services\Portal\Contracts\PortalDataGateway;
use App\Services\Portal\Gateways\ExternalPortalDataGateway;
use App\Services\Portal\Gateways\MockPortalDataGateway;

class PortalCatalogService
{
    public function __construct(private ?PortalDataGateway $gateway = null)
    {
        $this->gateway ??= new ExternalPortalDataGateway(new MockPortalDataGateway());
    }

    public function get(array $filters = []): array
    {
        $catalog = $this->gateway->catalog($filters);
        $source = (string) ($catalog['_source'] ?? 'mock-fallback');
        $externalMeta = $catalog['_meta'] ?? null;
        unset($catalog['_source']);
        $products = $catalog['products'] ?? [];

        if (in_array($source, ['external', 'external-cache'], true)) {
            return PortalResponse::make([
                'products' => $products,
                'filters' => $catalog['filters'] ?? [],
            ], array_merge($this->normalizeMeta($externalMeta, $filters, count($products)), ['source' => $source]));
        }

        $search = mb_strtolower(trim((string) ($filters['search'] ?? '')));
        $categories = $this->csvValues($filters['category'] ?? '');
        $brands = $this->csvValues($filters['brand'] ?? '');
        $maxPrice = $filters['max_price'] ?? null;
        $availability = trim((string) ($filters['availability'] ?? 'all'));

        $filtered = array_values(array_filter($products, function (array $product) use ($search, $categories, $brands, $maxPrice, $availability): bool {
            $haystack = mb_strtolower(implode(' ', [$product['name'] ?? '', $product['brand'] ?? '', $product['category'] ?? '', $product['id'] ?? '']));
            $matchesSearch = $search === '' || str_contains($haystack, $search);
            $matchesCategory = count($categories) === 0 || in_array((string) ($product['category'] ?? ''), $categories, true);
            $matchesBrand = count($brands) === 0 || in_array((string) ($product['brand'] ?? ''), $brands, true);
            $matchesPrice = $maxPrice === null || (float) ($product['price_value'] ?? 0) <= (float) $maxPrice;
            $matchesAvailability = $availability !== 'available' || (bool) ($product['is_available'] ?? $product['isAvailable'] ?? false);

            return $matchesSearch && $matchesCategory && $matchesBrand && $matchesPrice && $matchesAvailability;
        }));

        [$items, $meta] = $this->paginate($filtered, (int) ($filters['page'] ?? 1), (int) ($filters['per_page'] ?? 12));

        return PortalResponse::make([
            'products' => $items,
            'filters' => $catalog['filters'] ?? [],
        ], array_merge($meta, ['source' => $source]));
    }

    private function paginate(array $items, int $page, int $perPage): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(96, $perPage));
        $total = count($items);

        return [array_slice($items, ($page - 1) * $perPage, $perPage), [
            'current_page' => $page,
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => (int) max(1, ceil($total / $perPage)),
        ]];
    }

    private function normalizeMeta(?array $meta, array $filters, int $count): array
    {
        $perPage = max(1, (int) ($meta['per_page'] ?? $filters['per_page'] ?? max(1, $count)));
        $total = max(0, (int) ($meta['total'] ?? $count));
        $currentPage = max(1, (int) ($meta['current_page'] ?? $meta['page'] ?? $filters['page'] ?? 1));

        return [
            'current_page' => $currentPage,
            'page' => $currentPage,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => (int) max(1, ceil($total / $perPage)),
        ];
    }

    private function csvValues(mixed $value): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $value)), fn (string $item): bool => $item !== ''));
    }
}
