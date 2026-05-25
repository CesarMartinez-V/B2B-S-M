<?php

namespace App\Services\Portal;

use App\Services\Portal\Contracts\PortalDataGateway;
use App\Services\Portal\Gateways\ExternalPortalDataGateway;
use App\Services\Portal\Gateways\MockPortalDataGateway;

class PortalQuotesService
{
    public function __construct(private ?PortalDataGateway $gateway = null)
    {
        $this->gateway ??= new ExternalPortalDataGateway(new MockPortalDataGateway());
    }

    public function get(array $filters = []): array
    {
        $payload = $this->gateway->quotes($filters);
        $source = (string) ($payload['_source'] ?? 'mock');
        $externalMeta = is_array($payload['_meta'] ?? null) ? $payload['_meta'] : [];
        unset($payload['_source']);
        unset($payload['_meta']);

        if (in_array($source, ['external', 'external-cache'], true) && $externalMeta !== []) {
            return PortalResponse::make($payload, array_merge($externalMeta, ['source' => $source]));
        }

        $search = mb_strtolower(trim((string) ($filters['search'] ?? '')));
        $status = trim((string) ($filters['status'] ?? ''));
        $archived = array_key_exists('archived', $filters) ? filter_var($filters['archived'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : null;

        $quotes = array_values(array_filter($payload['quotes'] ?? [], function (array $quote) use ($search, $status, $archived): bool {
            $haystack = mb_strtolower(implode(' ', [$quote['id'] ?? '', $quote['client'] ?? '', $quote['ref'] ?? '', $quote['vehicle'] ?? '', $quote['brand'] ?? '']));

            return ($search === '' || str_contains($haystack, $search))
                && ($status === '' || ($quote['status'] ?? '') === $status)
                && ($archived === null || (bool) ($quote['archived'] ?? false) === $archived);
        }));

        [$items, $meta] = $this->paginate($quotes, (int) ($filters['page'] ?? 1), (int) ($filters['per_page'] ?? 15));
        $payload['quotes'] = $items;

        return PortalResponse::make($payload, array_merge($meta, ['source' => $source]));
    }

    public function create(array $payload): array
    {
        $items = array_values($payload['items'] ?? []);
        $amount = array_reduce($items, fn (float $sum, array $item): float => $sum + ((float) ($item['price'] ?? 0) * (int) ($item['qty'] ?? 1)), 0.0);
        $id = '#QT-TMP-'.substr((string) time(), -6);

        return PortalResponse::make([
            'quote' => [
                'id' => $id,
                'client' => $payload['client'] ?? 'Cliente temporal',
                'ref' => $payload['ref'] ?? 'REF: Carrito temporal',
                'vehicle' => $payload['vehicle'] ?? 'Vehiculo por definir',
                'brand' => $payload['brand'] ?? 'Seleccion desde catalogo',
                'date' => 'Ahora',
                'amount' => $amount,
                'status' => 'pending',
                'archived' => false,
                'items' => $items,
            ],
            'message' => 'Cotización creada correctamente.',
        ], ['source' => 'mock', 'persisted' => false]);
    }

    private function paginate(array $items, int $page, int $perPage): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $total = count($items);

        return [array_slice($items, ($page - 1) * $perPage, $perPage), [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => (int) max(1, ceil($total / $perPage)),
        ]];
    }
}
