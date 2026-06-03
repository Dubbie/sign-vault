<?php

namespace App\Http\Controllers;

use App\Models\Sign;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class StatsController extends Controller
{
    public function index(): JsonResponse
    {
        $totalUsers = User::notBanned()->count();
        $totalSigns = Sign::count();

        $cdnLatencyMs = $this->measureCdnLatency();

        $uptimePercentage = $this->fetchUptimePercentage();
        $serverUptime = $this->getServerUptime();

        return response()->json([
            'total_users' => $totalUsers,
            'total_signs' => $totalSigns,
            'cdn_latency_ms' => $cdnLatencyMs,
            'uptime_percentage' => $uptimePercentage,
            'server_uptime_seconds' => $serverUptime,
            'is_up' => true,
        ]);
    }

    private function fetchUptimePercentage(): ?float
    {
        $apiKey = config('services.uptimerobot.api_key');
        $monitorId = config('services.uptimerobot.monitor_id');

        if ($apiKey === null || $apiKey === '' || $monitorId === null || $monitorId === '') {
            return null;
        }

        return Cache::remember('uptime_percentage', 300, function () use ($apiKey, $monitorId): ?float {
            try {
                $response = Http::asForm()->post('https://api.uptimerobot.com/v2/getMonitors', [
                    'api_key' => $apiKey,
                    'format' => 'json',
                    'monitors' => $monitorId,
                    'custom_uptime_ratios' => 30,
                ]);

                $data = $response->json();

                if (($data['stat'] ?? null) !== 'ok') {
                    return null;
                }

                $uptimeRatio = $data['monitors'][0]['custom_uptime_ratio'] ?? $data['monitors'][0]['uptime_ratio'] ?? null;

                if ($uptimeRatio === null) {
                    return null;
                }

                return (float) $uptimeRatio;
            } catch (\Exception) {
                return null;
            }
        });
    }

    private function measureCdnLatency(): ?int
    {
        $disk = config('filesystems.default');

        if ($disk !== 's3') {
            return null;
        }

        return Cache::remember('cdn_latency', 60, function (): ?int {
            $url = config('filesystems.disks.s3.url');

            if ($url === null || $url === '') {
                return null;
            }

            $start = microtime(true);

            try {
                Http::timeout(5)->head($url);

                return (int) ((microtime(true) - $start) * 1000);
            } catch (\Exception) {
                return null;
            }
        });
    }

    private function getServerUptime(): ?int
    {
        if (is_readable('/proc/uptime')) {
            $uptime = (float) file_get_contents('/proc/uptime');

            return (int) $uptime;
        }

        return null;
    }
}
