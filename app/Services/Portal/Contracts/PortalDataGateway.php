<?php

namespace App\Services\Portal\Contracts;

interface PortalDataGateway
{
    public function dashboard(array $filters = []): array;

    public function catalog(array $filters = []): array;

    public function brands(array $filters = []): array;

    public function orders(array $filters = []): array;

    public function account(array $filters = []): array;

    public function invoices(array $filters = []): array;

    public function quotes(array $filters = []): array;

    public function profile(array $filters = []): array;
}
