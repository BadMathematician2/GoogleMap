<?php


namespace App\Packages\GoogleMap\src;


use App\Models\GoogleObject;

class GoogleMap
{
    const URL = 'https://maps.googleapis.com/maps/api/place/nearbysearch/json?';

    public function getObjects($latitude, $longitude, $radius, $api_key)
    {
        $url = $this->getUrl($latitude, $longitude, $radius, $api_key);

        do {
            sleep(2);
            $results = json_decode(file_get_contents($url), true);

            foreach ($results['results'] as $data) {
                $this->setInDB($data);
            }

            if ($this->isNext($results)) {
                $url = $this->nextUrl($results, $api_key);
            }
        } while ($this->isNext($results));

    }

    private function setInDB($data)
    {
        GoogleObject::query()->create(['data' => json_encode($data), 'lat' => $this->getLat($data), 'lng' => $this->getLng($data)]);
    }

    private function getLat($data)
    {
        return $data['geometry']['location']['lat'];
    }

    private function getLng($data)
    {
        return $data['geometry']['location']['lng'];
    }

    private function getUrl($latitude, $longitude, $radius, $api_key)
    {
        return self::URL . "location=$latitude,$longitude&radius=$radius&key=$api_key";
    }

    private function isNext($results)
    {
        return isset($results['next_page_token']);
    }

    private function nextUrl($results, $api_key)
    {
        return self::URL . "pagetoken=" . $results['next_page_token'] . "&key=$api_key";
    }

}
