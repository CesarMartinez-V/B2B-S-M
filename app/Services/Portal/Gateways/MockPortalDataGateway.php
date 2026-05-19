<?php

namespace App\Services\Portal\Gateways;

use App\Data\Portal\PortalMockData;
use App\Services\Portal\Contracts\PortalDataGateway;

class MockPortalDataGateway implements PortalDataGateway
{
    /**
     * Fallback gateway used until the external platform contract is available.
     */
    public function dashboard(array $filters = []): array
    {
        return PortalMockData::dashboard();
    }

    public function catalog(array $filters = []): array
    {
        return PortalMockData::catalog();
    }

    public function brands(array $filters = []): array
    {
        return PortalMockData::brands();
    }

    public function orders(array $filters = []): array
    {
        return PortalMockData::orders();
    }

    public function account(array $filters = []): array
    {
        return PortalMockData::account();
    }

    public function invoices(array $filters = []): array
    {
        return PortalMockData::invoices();
    }

    public function quotes(array $filters = []): array
    {
        return PortalMockData::quotes();
    }

    public function profile(array $filters = []): array
    {
        return PortalMockData::profile();
    }
}
