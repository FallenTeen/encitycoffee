<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Http\Request;

// Clear cache
\Illuminate\Support\Facades\Cache::clear();

// Login sebagai it_support
$user = User::where('role', 'it_support')->first();
auth()->login($user);

// Create request
$request = new Request();
$request->setMethod('GET');
$request->server->set('REQUEST_URI', '/produk');

// Call controller
$controller = app(\App\Http\Controllers\ProdukController::class);

try {
    $response = $controller->index($request);

    // For Inertia response, get the props
    if (method_exists($response, 'getProps')) {
        $props = $response->getProps();
        echo "=== Inertia Props ===\n";
        echo json_encode([
            'produks' => [
                'data_count' => count($props['produks']['data'] ?? []),
                'total' => $props['produks']['total'] ?? null,
                'current_page' => $props['produks']['current_page'] ?? null,
                'last_page' => $props['produks']['last_page'] ?? null,
                'prev_page_url' => $props['produks']['prev_page_url'] ?? null,
                'next_page_url' => $props['produks']['next_page_url'] ?? null,
            ],
            'kategori_list_count' => count($props['kategori_list'] ?? []),
            'can_manage' => $props['canManageProduk'] ?? null,
        ], JSON_PRETTY_PRINT) . "\n";
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
