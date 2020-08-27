<?php


namespace App\Packages\GoogleMap\src;


use App\Models\GoogleObject;

class SearchGoogleMap
{
    const URL = 'https://maps.googleapis.com/maps/api/place/nearbysearch/json?';

    const LAT_METER = 0.000012;
    const LNG_METER = 0.000009;

    private $api_key;
    public function __construct($api_key)
    {
        $this->api_key = $api_key;
    }

    public function getObjects($latitude, $longitude, $radius)
    {
        $url = $this->getUrl($latitude, $longitude, $radius);

        $results = json_decode(file_get_contents($url), true);

        foreach ($results['results'] as $data) {
            try {
                $this->setInDB($data);
            } catch (\Exception $exception){}
        }

        return $results;
    }

    private function setInDB($data)
    {
        GoogleObject::query()->create(['data' => json_encode($data), 'place_id' => $this->getPlaceId($data)]);
    }


    private function getPlaceId($data)
    {
        return $data['place_id'];
    }


    private function getUrl($latitude, $longitude, $radius)
    {
        return self::URL . "location=$latitude,$longitude&radius=$radius&key=$this->api_key";
    }

    /**
     * @param float $lat1 | left down
     * @param float $lng1 |
     * @param float $lat2 | right up
     * @param float $lng2 |
     * @param float $radius
     */
    public function searchInRectangle($lat1, $lng1, $lat2, $lng2, $radius)
    {
        $step = $radius * 2**0.5 / 2;

        for ($x = $this->latPlusMeters($lat1, $step); $x < $lat2; $x = $this->latPlusMeters($x, $step)) {
            for ($y = $this->lngPlusMeters($lng1, $step); $y < $lng2; $y = $this->lngPlusMeters($y, $step)) {
                $this->getObjects($x, $y, $radius);
            }
        }

    }
    public function searchIn($lat1, $lng1, $lat2, $lng2, $side, $r)
    {
        $radius = $side *  2**0.5 / 2;

        for ($x = $lat1; $x < $lat2; $x = $this->latPlusMeters($x, $side)) {
            for ($y = $lng1; $y < $lng2; $y = $this->lngPlusMeters($y, $side)) {
                if (! empty($this->getObjects($this->latPlusMeters($x, $side/2), $this->lngPlusMeters($y, $side/2), $radius)['results'])) {
                    $this->searchInRectangle($x, $y, $this->latPlusMeters($x, $side),  $this->lngPlusMeters($y, $side), $r);
                }
            }
        }
    }

    private function latPlusMeters($latitude, $meters)
    {
        return $latitude + self::LAT_METER * $meters;
    }
    private function lngPlusMeters($longitude, $meters)
    {
        return $longitude + self::LNG_METER * $meters;
    }

}
