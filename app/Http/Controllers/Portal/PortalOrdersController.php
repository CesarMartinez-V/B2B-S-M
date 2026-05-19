<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\Portal\PortalOrdersService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortalOrdersController extends Controller
{
    public function index(Request $request, PortalOrdersService $service): JsonResponse
    {
        return response()->json($service->get($request->query()));
    }
}
