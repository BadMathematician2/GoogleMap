<?php


namespace GoogleMap;


use Illuminate\Support\Facades\Facade;

class GoogleMapFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'GoogleMap';
    }

}
