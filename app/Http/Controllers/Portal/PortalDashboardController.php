<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\Portal\PortalDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortalDashboardController extends Controller
{
    public function show(Request $request, PortalDashboardService $service): JsonResponse
    {
        return response()->json($service->get($request->query()));
    }
}
