<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\Portal\PortalInvoicesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortalInvoicesController extends Controller
{
    public function index(Request $request, PortalInvoicesService $service): JsonResponse
    {
        return response()->json($service->get($request->query()));
    }
}
