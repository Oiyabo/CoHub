<?php

namespace App\Providers;

use App\Models\Community;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('*', function ($view) {
            try {
                if (Schema::hasTable('communities')) {
                    $view->with('globalCommunities', Community::withCount('posts')->get());
                }
            } catch (\Throwable $e) {
                $view->with('globalCommunities', collect());
            }
        });
    }
}
