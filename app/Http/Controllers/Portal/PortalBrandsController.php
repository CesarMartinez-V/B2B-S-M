<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\Portal\PortalBrandsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortalBrandsController extends Controller
{
    public function index(Request $request, PortalBrandsService $service): JsonResponse
    {
        return response()->json($service->get($request->query()));
    }
}
