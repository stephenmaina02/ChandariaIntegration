<?php

namespace App\Providers;

use App\Models\Order;
use App\Observers\OrderPostIpObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Existing OAuth clients were created with integer IDs under Passport 10.
        Passport::$clientUuids = false;
    }

    public function boot(): void
    {
        JsonResource::withoutWrapping();

        Order::observe(OrderPostIpObserver::class);

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(600)->by($request->user()?->id ?: $request->ip());
        });
    }
}
