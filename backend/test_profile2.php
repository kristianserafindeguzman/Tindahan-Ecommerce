<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = App\Models\User::where('role', 'Vendor')->first();
$store = $user->store; // Lazy load
$responseData = [
    'user' => $user,
    'role' => $user->role,
];
echo json_encode($responseData, JSON_PRETTY_PRINT);
