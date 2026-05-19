<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\Portal\PortalProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortalProfileController extends Controller
{
    public function show(Request $request, PortalProfileService $service): JsonResponse
    {
        return response()->json($service->get($request->query()));
    }
}
