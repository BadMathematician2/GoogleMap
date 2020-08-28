<?php


namespace GoogleMap;


use Illuminate\Support\ServiceProvider;

class GoogleMapProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('GoogleMap', function () {
            return new SearchGoogleMap();
        });
    }
}
