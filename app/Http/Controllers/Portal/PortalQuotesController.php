<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\Portal\PortalQuotesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PortalQuotesController extends Controller
{
    public function index(Request $request, PortalQuotesService $service): JsonResponse
    {
        return response()->json($service->get($request->query()));
    }

    public function store(Request $request, PortalQuotesService $service): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'items' => ['required', 'array', 'min:1'],
            'items.*.name' => ['nullable', 'string', 'max:255'],
            'items.*.sku' => ['nullable', 'string', 'max:100'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:100'],
            'items.*.price' => ['nullable', 'numeric', 'min:0'],
            'observations' => ['nullable', 'string', 'max:500'],
            'comments' => ['nullable', 'string', 'max:500'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'data' => [
                    'created' => false,
                    'message' => 'Revise los productos y cantidades de la solicitud.',
                    'errors' => $validator->errors(),
                ],
                'meta' => ['source' => 'local-temporary', 'persisted' => false],
            ], 422);
        }

        return response()->json($service->create($validator->validated()), 201);
    }
}
