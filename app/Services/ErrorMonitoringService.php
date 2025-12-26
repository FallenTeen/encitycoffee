<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use App\Mail\CriticalErrorNotification;

class ErrorMonitoringService
{
    private const ERROR_THRESHOLD = 10; // Jumlah error sebelum mengirim notifikasi
    private const TIME_WINDOW = 300; // 5 menit
    private const CACHE_PREFIX = 'error_count_';
    
    public function logError(string $type, string $message, array $context = [], string $severity = 'error')
    {
        $errorId = uniqid('err_');
        $timestamp = now();
        
        $errorData = [
            'id' => $errorId,
            'type' => $type,
            'message' => $message,
            'context' => $context,
            'severity' => $severity,
            'timestamp' => $timestamp->toDateTimeString(),
            'file' => $context['file'] ?? '',
            'line' => $context['line'] ?? '',
            'trace' => $context['trace'] ?? ''
        ];
        
        // Log ke file khusus
        Log::channel('error_monitoring')->error("[$type] $message", $errorData);
        
        // Hitung error rate
        $this->incrementErrorCount($type);
        
        // Cek apakah perlu mengirim notifikasi
        if ($this->shouldSendNotification($type)) {
            $this->sendCriticalNotification($type, $errorData);
        }
        
        return $errorId;
    }
    
    private function incrementErrorCount(string $type)
    {
        $cacheKey = self::CACHE_PREFIX . $type;
        $count = Cache::get($cacheKey, 0);
        Cache::put($cacheKey, $count + 1, self::TIME_WINDOW);
    }
    
    private function shouldSendNotification(string $type): bool
    {
        $cacheKey = self::CACHE_PREFIX . $type;
        $count = Cache::get($cacheKey, 0);
        
        // Kirim notifikasi jika sudah mencapai threshold
        return $count >= self::ERROR_THRESHOLD;
    }
    
    private function sendCriticalNotification(string $type, array $errorData)
    {
        try {
            $adminEmail = config('mail.admin_email', 'admin@example.com');
            
            Log::warning('Sending critical error notification', [
                'type' => $type,
                'error_count' => self::ERROR_THRESHOLD,
                'admin_email' => $adminEmail
            ]);
            
            // Bisa ditambahkan pengiriman email di sini
            // Mail::to($adminEmail)->send(new CriticalErrorNotification($errorData));
            
        } catch (\Exception $e) {
            Log::error('Failed to send critical error notification', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    public function logApiResponse(string $endpoint, int $statusCode, float $responseTime, array $context = [])
    {
        $logData = [
            'endpoint' => $endpoint,
            'status_code' => $statusCode,
            'response_time_ms' => round($responseTime * 1000, 2),
            'timestamp' => now()->toDateTimeString(),
            'context' => $context
        ];
        
        if ($statusCode >= 400) {
            Log::channel('api_errors')->error("API Error: $endpoint", $logData);
        } elseif ($responseTime > 2.0) { // Response time > 2 detik
            Log::channel('api_performance')->warning("Slow API: $endpoint", $logData);
        } else {
            Log::channel('api_monitoring')->info("API: $endpoint", $logData);
        }
    }
    
    public function logDatabaseQuery(string $query, float $executionTime, array $bindings = [])
    {
        $logData = [
            'query' => $query,
            'execution_time_ms' => round($executionTime * 1000, 2),
            'timestamp' => now()->toDateTimeString(),
            'bindings' => $bindings
        ];
        
        if ($executionTime > 1.0) { // Query lambat > 1 detik
            Log::channel('slow_queries')->warning("Slow query detected", $logData);
        } else {
            Log::channel('database_monitoring')->info("Database query", $logData);
        }
    }
    
    public function getErrorRate(string $type): array
    {
        $cacheKey = self::CACHE_PREFIX . $type;
        $count = Cache::get($cacheKey, 0);
        
        return [
            'type' => $type,
            'error_count' => $count,
            'threshold' => self::ERROR_THRESHOLD,
            'time_window' => self::TIME_WINDOW,
            'within_threshold' => $count < self::ERROR_THRESHOLD
        ];
    }
}