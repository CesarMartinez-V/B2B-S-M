<?php

namespace App\Services\Portal;

use App\Services\Portal\Contracts\PortalDataGateway;
use App\Services\Portal\Gateways\ExternalPortalDataGateway;
use App\Services\Portal\Gateways\MockPortalDataGateway;

class PortalAccountService
{
    public function __construct(private ?PortalDataGateway $gateway = null)
    {
        $this->gateway ??= new ExternalPortalDataGateway(new MockPortalDataGateway());
    }

    public function get(array $filters = []): array
    {
        $account = $this->gateway->account($filters);
        $source = (string) ($account['_source'] ?? 'mock');
        unset($account['_source']);
        $filter = (string) ($filters['filter'] ?? 'Todos');
        $period = (string) ($filters['period'] ?? '');
        $limit = max(1, (int) ($filters['limit'] ?? 3));
        $movements = array_values(array_filter($account['movements'] ?? [], function (array $movement) use ($filter, $period): bool {
            $matchesFilter = $filter === 'Todos' || mb_strtolower($movement['status'] ?? '') === mb_strtolower($filter);
            $matchesPeriod = $period === '' || str_contains((string) ($movement['date'] ?? ''), $period);

            return $matchesFilter && $matchesPeriod;
        }));

        $account['movements'] = array_slice($movements, 0, $limit);
        $account['chart']['period'] = $period;

        return PortalResponse::make($account, ['source' => $source, 'total_movements' => count($movements)]);
    }
}
