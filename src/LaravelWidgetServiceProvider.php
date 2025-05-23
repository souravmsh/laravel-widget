<?php

namespace Souravmsh\LaravelWidget;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Souravmsh\LaravelWidget\View\Components\FontHunterWidget;
use Souravmsh\LaravelWidget\View\Components\AvatarWidget;
use Souravmsh\LaravelWidget\Facades\FontHunter;

class LaravelWidgetServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Load views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'laravel-widget');
      
        // Publish configuration
        $this->publishes([
            __DIR__ . '/../config/laravel_widget.php' => config_path('laravel_widget.php'),
        ], 'config');

        // Publish views
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/laravel_widget'),
        ], 'views');

        // Publish components
        Blade::component("laravel-widget::font-hunter", FontHunterWidget::class);
        Blade::component("laravel-widget::avatar", AvatarWidget::class);

        // Register route for form processing
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
    }

    public function register()
    {
        // Merge configuration
        $this->mergeConfigFrom(__DIR__ . '/../config/laravel_widget.php', 'laravel_widget');

        // Register FontHunter as a singleton
        $this->app->singleton(FontHunter::class, function () {
            return new FontHunter();
        });
    }
}
