<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\Portal\PortalCatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortalCatalogController extends Controller
{
    public function index(Request $request, PortalCatalogService $service): JsonResponse
    {
        return response()->json($service->get($request->query()));
    }
}
