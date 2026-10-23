<?php

namespace App\Providers;

use App\Models\ApprovalStatus;
use App\Models\Notification;
use App\Models\Order;
use App\Observers\ApprovalStatusObserver;
use App\Observers\NotificationObserver;
use App\Observers\OrderObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Derive a separate signing secret from the existing APP_KEY. No .env edits,
        // additional secrets on disk, or production secrets in frontend bundles.
        $secret = env('REVERB_APP_SECRET', '');
        $origins = config('realtime.allowed_origins') ?: array_values(array_unique(array_filter([
            'localhost', '127.0.0.1',
            parse_url(config('app.url', ''), PHP_URL_HOST),
            parse_url(config('services.frontend.url', ''), PHP_URL_HOST),
        ])));
        config([
            'broadcasting.connections.reverb.secret' => $secret,
            'broadcasting.connections.reverb.options.host' => env('REVERB_PUBLISH_HOST', '127.0.0.1'),
            'broadcasting.connections.reverb.options.port' => (int) env('REVERB_PUBLISH_PORT', 8080),
            'broadcasting.connections.reverb.options.scheme' => env('REVERB_PUBLISH_SCHEME', 'http'),
            'broadcasting.connections.reverb.options.useTLS' => env('REVERB_PUBLISH_SCHEME', 'http') === 'https',
            'reverb.apps.apps.0.secret' => $secret,
            'reverb.apps.apps.0.allowed_origins' => $origins,
            'reverb.servers.reverb.host' => config('realtime.server_host'),
            'reverb.servers.reverb.port' => config('realtime.server_port'),
        ]);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        require base_path('routes/channels.php');
        Notification::observe(NotificationObserver::class);
        Order::observe(OrderObserver::class);
        ApprovalStatus::observe(ApprovalStatusObserver::class);
    }
}
