<?php


namespace GoogleMap;


use GoogleMap\Repositories\ApiKeyRepository;
use GoogleMap\Repositories\Interfaces\ApiKeyRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class GoogleMapProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('GoogleMap', function () {
            return $this->app->make(SearchGoogleMap::class);
        });

        $this->app->bind(ApiKeyRepositoryInterface::class, ApiKeyRepository::class);
    }

    public function boot()
    {
        $this->migrationsRegister();
        $this->configsRegister();
        $this->commandRegister();
    }

    private function migrationsRegister()
    {
        $this->loadMigrationsFrom(__DIR__.'/migrations');
    }

    private function configsRegister()
    {
        $this->mergeConfigFrom(__DIR__ . '/config/api_key.php', 'api_key');
        $this->mergeConfigFrom(__DIR__ . '/config/search.php', 'search');
        $this->mergeConfigFrom(__DIR__ . '/config/places.php', 'places');
    }

    private function commandRegister()
    {
        $this->commands([
            \GoogleMap\Commands\SearchGoogleMap::class
        ]);
    }
}
