<?php

namespace App\Http\Controllers\Api\V1;

use Throwable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Api\BaseApiController;

#[Group('System')]
class HealthController extends BaseApiController
{
    /**
     * System Health & Liveness Probe
     *
     * Check status and latency of core infrastructure dependencies (database, cache, storage).
     */
    public function __invoke(): JsonResponse
    {
        $checks = [];
        $isHealthy = true;

        // 1. Check Database Connectivity & Latency
        try {
            $dbStart = microtime(true);
            DB::connection()->getPdo();
            $dbLatencyMs = round((microtime(true) - $dbStart) * 1000, 2);

            $checks['database'] = [
                'status' => 'healthy',
                'latency_ms' => $dbLatencyMs,
                'connection' => config('database.default'),
            ];
        } catch (Throwable $e) {
            $isHealthy = false;
            $checks['database'] = [
                'status' => 'unhealthy',
                'error' => app()->isProduction() ? 'Database connection failed' : $e->getMessage(),
            ];
        }

        // 2. Check Cache Store
        try {
            $cacheKey = 'health_check_' . \Illuminate\Support\Str::random(16);
            Cache::put($cacheKey, true, 10);
            $cacheRead = Cache::get($cacheKey) === true;
            Cache::forget($cacheKey);

            $checks['cache'] = [
                'status' => $cacheRead ? 'healthy' : 'degraded',
                'store' => config('cache.default'),
            ];
            if (! $cacheRead) {
                $isHealthy = false;
            }
        } catch (Throwable $e) {
            $isHealthy = false;
            $checks['cache'] = [
                'status' => 'unhealthy',
                'error' => app()->isProduction() ? 'Cache store unreachable' : $e->getMessage(),
            ];
        }

        // 3. Check Storage Disk
        try {
            $diskName = (string) config('media.disks.public', 'public');
            Storage::disk($diskName)->exists('health_check.txt');

            $checks['storage'] = [
                'status' => 'healthy',
                'disk' => $diskName,
            ];
        } catch (Throwable $e) {
            $isHealthy = false;
            $checks['storage'] = [
                'status' => 'unhealthy',
                'error' => app()->isProduction() ? 'Storage disk unreachable' : $e->getMessage(),
            ];
        }

        $statusCode = $isHealthy ? 200 : 503;

        return response()->json([
            'success' => $isHealthy,
            'status' => $isHealthy ? 'healthy' : 'degraded',
            'timestamp' => now()->toIso8601String(),
            'environment' => app()->environment(),
            'checks' => $checks,
        ], $statusCode);
    }
}
