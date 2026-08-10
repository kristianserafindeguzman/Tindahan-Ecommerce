<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$ds = new App\Services\DistanceService();

echo "Test 1 (Same): " . $ds->calculateDistance(14.5900, 121.0200, 14.5900, 121.0200) . "\n";
echo "Test 2 (Nearby): " . $ds->calculateDistance(14.5900, 121.0200, 14.5910, 121.0210) . "\n";
echo "Test 3 (Far): " . $ds->calculateDistance(14.5900, 121.0200, 14.6500, 121.1000) . "\n";
echo "Test 4 (Missing): " . var_export($ds->calculateDistance(null, null, 14.5900, 121.0200), true) . "\n";
