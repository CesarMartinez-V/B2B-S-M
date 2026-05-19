<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\Portal\PortalQuotesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortalQuotesController extends Controller
{
    public function index(Request $request, PortalQuotesService $service): JsonResponse
    {
        return response()->json($service->get($request->query()));
    }

    public function store(Request $request, PortalQuotesService $service): JsonResponse
    {
        return response()->json($service->create($request->all()), 201);
    }
}
