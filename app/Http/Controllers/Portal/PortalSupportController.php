<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\Portal\PortalSupportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortalSupportController extends Controller
{
    public function contact(Request $request, PortalSupportService $service): JsonResponse
    {
        return response()->json($service->contact($request->all()), 201);
    }
}
