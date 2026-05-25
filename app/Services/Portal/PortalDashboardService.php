<?php

namespace App\Services\Portal;

use App\Services\Portal\Contracts\PortalDataGateway;
use App\Services\Portal\Gateways\ExternalPortalDataGateway;
use App\Services\Portal\Gateways\MockPortalDataGateway;

class PortalDashboardService
{
    public function __construct(private ?PortalDataGateway $gateway = null)
    {
        $this->gateway ??= new ExternalPortalDataGateway(new MockPortalDataGateway());
    }

    public function get(array $filters = []): array
    {
        $payload = $this->gateway->dashboard($filters);
        $source = (string) ($payload['_source'] ?? 'mock');
        $externalMeta = is_array($payload['_meta'] ?? null) ? $payload['_meta'] : [];
        unset($payload['_source'], $payload['_meta']);

        return PortalResponse::make($payload, array_merge($externalMeta, ['source' => $source]));
    }
}
