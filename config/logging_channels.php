<?php

return [
    'error_monitoring' => [
        'driver' => 'daily',
        'path' => storage_path('logs/error_monitoring.log'),
        'level' => 'error',
        'days' => 30,
        'permission' => 0664,
    ],
    
    'api_monitoring' => [
        'driver' => 'daily',
        'path' => storage_path('logs/api_monitoring.log'),
        'level' => 'info',
        'days' => 14,
        'permission' => 0664,
    ],
    
    'api_errors' => [
        'driver' => 'daily',
        'path' => storage_path('logs/api_errors.log'),
        'level' => 'error',
        'days' => 30,
        'permission' => 0664,
    ],
    
    'api_performance' => [
        'driver' => 'daily',
        'path' => storage_path('logs/api_performance.log'),
        'level' => 'warning',
        'days' => 14,
        'permission' => 0664,
    ],
    
    'slow_queries' => [
        'driver' => 'daily',
        'path' => storage_path('logs/slow_queries.log'),
        'level' => 'warning',
        'days' => 30,
        'permission' => 0664,
    ],
    
    'database_monitoring' => [
        'driver' => 'daily',
        'path' => storage_path('logs/database_monitoring.log'),
        'level' => 'info',
        'days' => 7,
        'permission' => 0664,
    ],
];