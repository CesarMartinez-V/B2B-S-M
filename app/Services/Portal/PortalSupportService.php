<?php

namespace App\Services\Portal;

class PortalSupportService
{
    public function contact(array $payload): array
    {
        return PortalResponse::make([
            'ticket' => [
                'id' => 'SUP-'.now()->format('Ymd-His'),
                'status' => 'received',
                'subject' => $payload['subject'] ?? 'Soporte B2B',
            ],
            'message' => 'Solicitud de soporte registrada.',
        ], ['source' => 'mock', 'persisted' => false]);
    }
}
