<?php

namespace App\Services\Portal;

class PortalResponse
{
    public static function make(array $data, array $meta = []): array
    {
        return [
            'data' => $data,
            'meta' => array_merge([
                'source' => 'mock',
                'generated_at' => now()->toISOString(),
            ], $meta),
        ];
    }
}
