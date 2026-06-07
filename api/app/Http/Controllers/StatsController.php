<?php

namespace App\Http\Controllers;

use App\Models\Sign;
use App\Models\User;
use App\Services\HealthService;
use Illuminate\Http\JsonResponse;

class StatsController extends Controller
{
    public function __construct(private HealthService $healthService) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'total_users' => User::notBanned()->count(),
            'total_signs' => Sign::count(),
            'cdn_latency_ms' => $this->healthService->cdnLatencyMs(),
            'uptime_percentage' => $this->healthService->uptimePercentage(),
            'server_uptime_seconds' => $this->healthService->serverUptimeSeconds(),
            'is_up' => true,
        ]);
    }
}
