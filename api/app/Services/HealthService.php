<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class HealthService
{
    public function uptimePercentage(): ?float
    {
        $apiKey = config('services.uptimerobot.api_key');
        $monitorId = config('services.uptimerobot.monitor_id');

        if (! $apiKey || ! $monitorId) {
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

                $ratio = $data['monitors'][0]['custom_uptime_ratio']
                    ?? $data['monitors'][0]['uptime_ratio']
                    ?? null;

                return $ratio !== null ? (float) $ratio : null;
            } catch (\Exception) {
                return null;
            }
        });
    }

    public function cdnLatencyMs(): ?int
    {
        if (config('filesystems.default') !== 's3') {
            return null;
        }

        return Cache::remember('cdn_latency', 60, function (): ?int {
            $url = config('filesystems.disks.s3.url');

            if (! $url) {
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

    public function serverUptimeSeconds(): ?int
    {
        if (is_readable('/proc/uptime')) {
            return (int) (float) file_get_contents('/proc/uptime');
        }

        return null;
    }
}
