<?php
namespace TypiCMS\Modules\Settings\Providers;

use Lang;
use View;
use Config;
use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\Application;

// Model
use TypiCMS\Modules\Settings\Models\Setting;

// Repo
use TypiCMS\Modules\Settings\Repositories\EloquentSetting;

// Cache
use TypiCMS\Modules\Settings\Repositories\CacheDecorator;
use TypiCMS\Services\Cache\LaravelCache;

class ModuleProvider extends ServiceProvider
{

    public function boot()
    {
        // Bring in the routes
        require __DIR__ . '/../routes.php';

        // Add dirs
        View::addLocation(__DIR__ . '/../Views');
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'settings');
        $this->publishes([
            __DIR__ . '/../config/' => config_path('typicms/settings'),
        ], 'config');
        $this->publishes([
            __DIR__ . '/../migrations/' => base_path('/database/migrations'),
        ], 'migrations');
    }

    public function register()
    {

        $app = $this->app;

        $app->bind('TypiCMS\Modules\Settings\Repositories\SettingInterface', function (Application $app) {
            $repository = new EloquentSetting(new Setting);
            if (! Config::get('app.cache')) {
                return $repository;
            }
            $laravelCache = new LaravelCache($app['cache'], 'settings', 10);

            return new CacheDecorator($repository, $laravelCache);
        });
    }
}
