<?php


namespace GoogleMap;


use App\Packages\GoogleMap\src\Repositories\ApiKeyRepository;
use App\Packages\GoogleMap\src\Repositories\Interfaces\ApiKeyRepositoryInterface;
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
    }

    private function migrationsRegister()
    {
        $this->loadMigrationsFrom(__DIR__.'/migrations');
    }

    private function configsRegister()
    {
        $this->mergeConfigFrom(__DIR__ . '/config/api_key.php', 'api_key');
    }
}
