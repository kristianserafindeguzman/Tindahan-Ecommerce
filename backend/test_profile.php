<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = App\Models\User::where('role', 'Vendor')->first();
$user->load('store');
echo json_encode($user->toArray(), JSON_PRETTY_PRINT);
