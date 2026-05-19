<?php

namespace App\Services\Portal;

use App\Services\Portal\Contracts\PortalDataGateway;
use App\Services\Portal\Gateways\MockPortalDataGateway;

class PortalBrandsService
{
    public function __construct(private ?PortalDataGateway $gateway = null)
    {
        $this->gateway ??= new MockPortalDataGateway();
    }

    public function get(array $filters = []): array
    {
        return PortalResponse::make($this->gateway->brands($filters));
    }
}
