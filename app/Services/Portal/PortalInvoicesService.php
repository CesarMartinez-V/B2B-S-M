<?php

namespace App\Services\Portal;

use App\Services\Portal\Contracts\PortalDataGateway;
use App\Services\Portal\Gateways\ExternalPortalDataGateway;
use App\Services\Portal\Gateways\MockPortalDataGateway;

class PortalInvoicesService
{
    public function __construct(private ?PortalDataGateway $gateway = null)
    {
        $this->gateway ??= new ExternalPortalDataGateway(new MockPortalDataGateway());
    }

    public function get(array $filters = []): array
    {
        $payload = $this->gateway->invoices($filters);
        $source = (string) ($payload['_source'] ?? 'mock');
        unset($payload['_source']);
        $query = mb_strtolower(trim((string) ($filters['query'] ?? '')));
        $status = (string) ($filters['status'] ?? 'Todos');
        $from = $filters['from'] ?? null;
        $to = $filters['to'] ?? null;

        $invoices = array_values(array_filter($payload['invoices'] ?? [], function (array $invoice) use ($query, $status, $from, $to): bool {
            $haystack = mb_strtolower(implode(' ', [$invoice['number'] ?? '', $invoice['status'] ?? '', $invoice['total'] ?? '', $invoice['seller'] ?? '']));
            $invoiceTime = $this->dateValue((string) ($invoice['date'] ?? ''));
            $fromTime = $from ? strtotime((string) $from) : null;
            $toTime = $to ? strtotime((string) $to) : null;

            return ($query === '' || str_contains($haystack, $query))
                && ($status === 'Todos' || ($invoice['status'] ?? '') === $status)
                && (!$fromTime || $invoiceTime >= $fromTime)
                && (!$toTime || $invoiceTime <= $toTime);
        }));

        [$items, $meta] = $this->paginate($invoices, (int) ($filters['page'] ?? 1), (int) ($filters['per_page'] ?? 15));
        $payload['invoices'] = $items;
        $payload['statuses'] = array_values(array_unique(array_merge(['Todos'], array_column($this->gateway->invoices()['invoices'] ?? [], 'status'))));

        return PortalResponse::make($payload, array_merge($meta, ['source' => $source]));
    }

    private function dateValue(string $date): int
    {
        $normalized = str_replace(['Abr', ','], ['Apr', ''], $date);

        return strtotime($normalized) ?: 0;
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
