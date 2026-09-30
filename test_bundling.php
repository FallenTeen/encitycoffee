<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

config(['database.connections.mysql.database' => 'db_encityprod']);
\DB::reconnect();

// Mock Auth as kasir in cabang 1
$user = \App\Models\User::where('role', 'kasir')->first();
\Auth::login($user);

$req = Illuminate\Http\Request::create('/api/pos/bundling', 'GET', ['cabang_id' => 1]);
$ctrl = new \App\Http\Controllers\MobileBundlingController();
echo $ctrl->index($req)->getContent();
