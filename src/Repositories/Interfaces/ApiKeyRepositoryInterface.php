<?php


namespace App\Packages\GoogleMap\src\Repositories\Interfaces;


interface ApiKeyRepositoryInterface
{
    /**
     * @param string $api_key
     * @return mixed
     */
    public function addKey(string $api_key);

    public function updateStatuses();

    /**
     * @param string $api_key
     * @return mixed
     */
    public function changeApiKey(string $api_key);

    /**
     * @param string $api_key
     * @return mixed
     */
    public function makeInActive(string $api_key);

    public function takeApiKey();

}
