<?php


namespace GoogleMap;


use App\Packages\GoogleMap\src\Repositories\ApiKeyRepository;
use GoogleMap\Models\GoogleObject;

class SearchGoogleMap
{
    const URL = 'https://maps.googleapis.com/maps/api/place/nearbysearch/json?';

    const LAT_METER = 0.000012;
    const LNG_METER = 0.000009;

    private $controller;

    private $api_key;

    /**
     * @return \Illuminate\Database\Eloquent\HigherOrderBuilderProxy|mixed
     */
    public function getApiKey()
    {
        return $this->api_key;
    }

    /**
     * @param mixed $api_key
     */
    public function setApiKey($api_key): void
    {
        $this->api_key = $api_key;
    }

    /**
     * @return ApiKeyRepository
     */
    public function getController(): ApiKeyRepository
    {
        return $this->controller;
    }

    /**
     * SearchGoogleMap constructor.
     * @param ApiKeyRepository $controller
     */
    public function __construct(ApiKeyRepository $controller)
    {
        $this->setApiKey($controller->takeApiKey());

        $this->controller = $controller;
    }

    /**
     * знаходить і записує обʼєкти в крузі float $latitude, float $longitude, float $radius
     * @param float $latitude
     * @param float $longitude
     * @param float $radius
     * @return mixed
     */
    public function getObjects(float $latitude, float $longitude, float $radius)
    {
        $url = $this->getUrl($latitude, $longitude, $radius);

        try {
            $results = json_decode(file_get_contents($url), true);

            while($results['status'] != 'OK' && $results['status'] != 'ZERO_RESULTS') {
                $this->setApiKey($this->getController()->changeApiKey($this->getApiKey()));
                $results = json_decode(file_get_contents($url), true);
            }

            foreach ($results['results'] as $data) {
                try {
                    $this->setInDB($data);
                } catch (\Exception $exception){}
            }

            return $results;

        } catch (\Exception $exception) {
            \Log::info($exception->getMessage());
        }


        return null;
    }

    /**
     * @param array $data
     */
    private function setInDB(array $data)
    {
        GoogleObject::query()->create(['data' => json_encode($data), 'place_id' => $this->getPlaceId($data)]);
    }

    /**
     * @param array $data
     * @return mixed
     */
    private function getPlaceId(array $data)
    {
        return $data['place_id'];
    }

    /**
     * @param float $latitude
     * @param float $longitude
     * @param float $radius
     * @return string
     */
    private function getUrl(float $latitude, float $longitude, float $radius)
    {
        return self::URL . "location=$latitude,$longitude&radius=$radius&key={$this->getApiKey()}";
    }

    /**
     * пошук у прямокутнику float $lat1, float $lng1, float $lat2, float $lng2, колами радіуса float $radius
     *
     * @param float $lat1 | left down
     * @param float $lng1 |
     * @param float $lat2 | right up
     * @param float $lng2 |
     * @param float $radius
     */
    public function searchInRectangle(float $lat1, float $lng1, float $lat2, float $lng2, float $radius)
    {
        $step = $radius * 2**0.5; //крок сітки

        for ($x = $this->latPlusMeters($lat1, $step / 2); $x < $lat2; $x = $this->latPlusMeters($x, $step)) {
            for ($y = $this->lngPlusMeters($lng1, $step / 2); $y < $lng2; $y = $this->lngPlusMeters($y, $step)) {
                $this->getObjects($x, $y, $radius);
            }
        }

    }

    /**
     * Пошук у прямокутнику float $lat1, float $lng1 - нижня ліва точка, float $lat2, float $lng2 - верхня ліва точка,
     * його розбиваються на квадрати зі стороною float $lng2, робиться пошук у колі описаному, навколо даного квадрата
     * якщо результатів пошуку більше 20, то в самому квадраті робится пошук по колах радіуса float $r
     *
     * @param float $lat1
     * @param float $lng1
     * @param float $lat2
     * @param float $lng2
     * @param float $side
     * @param float $r
     */
    public function searchIn(float $lat1, float $lng1, float $lat2, float $lng2, float $side, float $r)
    {
        $radius = $side *  2**0.5 / 2; // радіус кола описаного навколо квадрата

        // робимо розбиття на квадрати
        for ($x = $lat1; $x < $lat2; $x = $this->latPlusMeters($x, $side)) {
            for ($y = $lng1; $y < $lng2; $y = $this->lngPlusMeters($y, $side)) {
                if (count($this->getObjects($this->latPlusMeters($x, $side/2), $this->lngPlusMeters($y, $side/2), $radius)['results']) >= 20) {
                    $this->searchInRectangle($x, $y, $this->latPlusMeters($x, $side),  $this->lngPlusMeters($y, $side), $r);
                }
            }
        }
    }

    /**
     * Повертає широту, на float $meters дальшу від початкової
     *
     * @param float $latitude
     * @param float $meters
     * @return float|int
     */
    private function latPlusMeters(float $latitude, float $meters)
    {
        return $latitude + self::LAT_METER * $meters;
    }

    /**
     *  Повертає довготу, на float $meters дальшу від початкової
     *
     * @param float $longitude
     * @param float $meters
     * @return float|int
     */
    private function lngPlusMeters(float $longitude, float $meters)
    {
        return $longitude + self::LNG_METER * $meters;
    }
}
