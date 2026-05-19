<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\Portal\PortalAccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortalAccountController extends Controller
{
    public function show(Request $request, PortalAccountService $service): JsonResponse
    {
        return response()->json($service->get($request->query()));
    }
}
