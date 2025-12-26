<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PerformanceMonitoringService
{
    private const METRICS_PREFIX = 'performance_metrics';
    private const ALERT_THRESHOLD_MS = 2000; // 2 seconds
    private const MEMORY_THRESHOLD_MB = 128; // 128 MB

    /**
     * Track API response time
     */
    public function trackApiResponse(string $endpoint, float $responseTime, int $statusCode, array $context = []): void
    {
        $metricKey = self::METRICS_PREFIX . '_api_' . str_replace('/', '_', $endpoint);
        
        // Store in cache for real-time monitoring
        Cache::put($metricKey . '_last_response_time', $responseTime, 300); // 5 minutes
        Cache::increment($metricKey . '_total_requests');
        
        if ($responseTime > self::ALERT_THRESHOLD_MS) {
            Cache::increment($metricKey . '_slow_requests');
            
            Log::warning('Slow API response detected', [
                'endpoint' => $endpoint,
                'response_time_ms' => $responseTime,
                'status_code' => $statusCode,
                'threshold_ms' => self::ALERT_THRESHOLD_MS,
                'context' => $context,
                'timestamp' => now()->toDateTimeString()
            ]);
        }

        // Log for analysis
        Log::info('API Response Time', [
            'endpoint' => $endpoint,
            'response_time_ms' => $responseTime,
            'status_code' => $statusCode,
            'memory_usage_mb' => memory_get_usage(true) / 1024 / 1024,
            'timestamp' => now()->toDateTimeString()
        ]);
    }

    /**
     * Track database query performance
     */
    public function trackDatabaseQuery(string $query, float $executionTime, array $bindings = []): void
    {
        if ($executionTime > 1000) { // 1 second threshold for queries
            Log::warning('Slow database query detected', [
                'query' => $query,
                'execution_time_ms' => $executionTime,
                'bindings' => $bindings,
                'timestamp' => now()->toDateTimeString()
            ]);
        }

        Log::info('Database Query Performance', [
            'query' => $query,
            'execution_time_ms' => $executionTime,
            'bindings' => $bindings,
            'timestamp' => now()->toDateTimeString()
        ]);
    }

    /**
     * Monitor memory usage
     */
    public function monitorMemoryUsage(string $operation, float $memoryBefore, float $memoryAfter): void
    {
        $memoryUsed = ($memoryAfter - $memoryBefore) / 1024 / 1024; // Convert to MB
        
        if ($memoryUsed > self::MEMORY_THRESHOLD_MB) {
            Log::warning('High memory usage detected', [
                'operation' => $operation,
                'memory_used_mb' => $memoryUsed,
                'memory_before_mb' => $memoryBefore / 1024 / 1024,
                'memory_after_mb' => $memoryAfter / 1024 / 1024,
                'threshold_mb' => self::MEMORY_THRESHOLD_MB,
                'timestamp' => now()->toDateTimeString()
            ]);
        }

        Log::info('Memory Usage', [
            'operation' => $operation,
            'memory_used_mb' => $memoryUsed,
            'memory_before_mb' => $memoryBefore / 1024 / 1024,
            'memory_after_mb' => $memoryAfter / 1024 / 1024,
            'timestamp' => now()->toDateTimeString()
        ]);
    }

    /**
     * Get performance metrics for a specific endpoint
     */
    public function getMetrics(string $endpoint): array
    {
        $metricKey = self::METRICS_PREFIX . '_api_' . str_replace('/', '_', $endpoint);
        
        $totalRequests = Cache::get($metricKey . '_total_requests', 0);
        $slowRequests = Cache::get($metricKey . '_slow_requests', 0);
        $lastResponseTime = Cache::get($metricKey . '_last_response_time', 0);
        
        return [
            'endpoint' => $endpoint,
            'total_requests' => $totalRequests,
            'slow_requests' => $slowRequests,
            'slow_request_percentage' => $totalRequests > 0 ? round(($slowRequests / $totalRequests) * 100, 2) : 0,
            'last_response_time_ms' => round($lastResponseTime, 2),
            'average_response_time_ms' => $this->calculateAverageResponseTime($endpoint),
            'status' => $lastResponseTime > self::ALERT_THRESHOLD_MS ? 'slow' : 'normal'
        ];
    }

    /**
     * Calculate average response time from logs
     */
    private function calculateAverageResponseTime(string $endpoint): float
    {
        // This would typically query a metrics database
        // For now, return a placeholder
        return 0.0;
    }

    /**
     * Generate performance report
     */
    public function generateReport(string $period = '24h'): array
    {
        $report = [
            'period' => $period,
            'generated_at' => now()->toDateTimeString(),
            'summary' => [
                'total_endpoints_monitored' => 0,
                'slow_endpoints' => 0,
                'average_response_time' => 0,
                'memory_alerts' => 0
            ],
            'endpoints' => []
        ];

        // Monitor key endpoints
        $endpoints = [
            '/api/pos/produk',
            '/api/pos/shift/buka',
            '/api/pos/transaksi',
            '/api/pos/auth/login'
        ];

        foreach ($endpoints as $endpoint) {
            $metrics = $this->getMetrics($endpoint);
            $report['endpoints'][] = $metrics;
            
            $report['summary']['total_endpoints_monitored']++;
            if ($metrics['slow_request_percentage'] > 5) {
                $report['summary']['slow_endpoints']++;
            }
        }

        return $report;
    }

    /**
     * Monitor database connection pool
     */
    public function monitorDatabaseConnections(): array
    {
        try {
            $connections = DB::select('SHOW STATUS LIKE "Threads_%"');
            $status = [];
            
            foreach ($connections as $connection) {
                $status[$connection->Variable_name] = $connection->Value;
            }

            Log::info('Database Connection Status', [
                'threads_connected' => $status['Threads_connected'] ?? 0,
                'threads_running' => $status['Threads_running'] ?? 0,
                'threads_cached' => $status['Threads_cached'] ?? 0,
                'timestamp' => now()->toDateTimeString()
            ]);

            return $status;
        } catch (\Exception $e) {
            Log::error('Failed to monitor database connections', [
                'error' => $e->getMessage(),
                'timestamp' => now()->toDateTimeString()
            ]);
            
            return [];
        }
    }

    /**
     * Setup performance monitoring middleware
     */
    public function setupMiddleware(): void
    {
        // This would be called in a service provider
        // to register middleware for automatic monitoring
        
        app()->terminating(function () {
            // Log performance metrics at the end of request
            $this->logRequestMetrics();
        });
    }

    /**
     * Log request metrics
     */
    private function logRequestMetrics(): void
    {
        $startTime = defined('LARAVEL_START') ? LARAVEL_START : microtime(true);
        $responseTime = (microtime(true) - $startTime) * 1000;
        
        Log::info('Request Performance', [
            'url' => request()->url(),
            'method' => request()->method(),
            'response_time_ms' => $responseTime,
            'memory_peak_mb' => memory_get_peak_usage(true) / 1024 / 1024,
            'db_queries' => DB::getQueryLog() ? count(DB::getQueryLog()) : 0,
            'timestamp' => now()->toDateTimeString()
        ]);
    }
}