<?php


namespace GoogleMap;


use App\Packages\GoogleMap\src\Exceptions\InvalidKeyException;
use App\Packages\GoogleMap\src\Models\Request;
use App\Packages\GoogleMap\src\Repositories\ApiKeyRepository;
use GoogleMap\Models\GoogleObject;

class SearchGoogleMap
{
    const URL = 'https://maps.googleapis.com/maps/api/place/nearbysearch/json?';

    const LAT_METER = 0.000012;
    const LNG_METER = 0.000009;
    const SQR_TWO = 2**0.5;


    /**
     * @var ApiKeyRepository
     */
    private $apiKeyRepository;
    /**
     * @var mixed
     */
    private $api_key;

    /**
     * @return \Illuminate\Database\Eloquent\HigherOrderBuilderProxy|mixed
     */
    public function getApiKey()
    {
        return $this->api_key;
    }

    public function update(): void
    {
        $this->api_key = $this->getApiKeyRepository()->changeApiKey($this->getApiKey());
    }

    /**
     * @return ApiKeyRepository
     */
    public function getApiKeyRepository(): ApiKeyRepository
    {
        return $this->apiKeyRepository;
    }

    /**
     * SearchGoogleMap constructor.
     * @param ApiKeyRepository $controller
     */
    public function __construct(ApiKeyRepository $controller)
    {
        $this->api_key = $controller->takeApiKey();

        $this->apiKeyRepository = $controller;
    }

    /**
     * знаходить і записує обʼєкти в крузі float $latitude, float $longitude, float $radius
     * @param float $latitude
     * @param float $longitude
     * @param float $radius
     * @return mixed
     * @throws InvalidKeyException
     */
    public function getObjects(float $latitude, float $longitude, float $radius)
    {
        if (Request::query()->where('circle', $latitude . ',' . $longitude . ',' . $radius)->exists()) {
            return null;
        }

        $url = $this->getUrl($latitude, $longitude, $radius);

        try {
            $results = json_decode(file_get_contents($url), true);
            $statuses = config('search')['status'];

            if (in_array($results['status'], $statuses)) {
                foreach ($results['results'] as $data) {
                    try {
                        $this->addInDb($data);
                    } catch (\Exception $exception) {

                    }
                }

                Request::query()->create(['data' => json_encode($results), 'circle' => $latitude . ',' . $longitude . ',' . $radius ]);

                return $results;
            }

        } catch (\Exception $exception) {
            \Log::info($exception->getMessage());

            return null;
        }

        throw new InvalidKeyException();
    }

    /**
     * @param array $data
     */
    private function addInDb(array $data)
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
        $params = [
            'location' => $latitude . ',' . $longitude,
            'radius' => $radius,
            'key' => $this->getApiKey()
        ];
        foreach ($params as $key => $value) {
            $params[$key] = $key . '='. $value;
        }

        return self::URL . implode('&', $params);
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
        $step = $radius * self::SQR_TWO; //крок сітки

        for ($x = $this->latPlusMeters($lat1, $step / 2); $x < $lat2; $x = $this->latPlusMeters($x, $step)) {
            for ($y = $this->lngPlusMeters($lng1, $step / 2); $y < $lng2; $y = $this->lngPlusMeters($y, $step)) {
                try {
                    $this->getObjects($x, $y, $radius);
                } catch (InvalidKeyException $e) {
                    $this->update();
                    $y = $this->lngPlusMeters($y, -1 * $step);
                }
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
     */
    public function searchIn(float $lat1, float $lng1, float $lat2, float $lng2)
    {
        $side = config('search')['side'];

        $radius = $side /  self::SQR_TWO; // радіус кола описаного навколо квадрата
        $r = config('search')['radius'];
        // робимо розбиття на квадрати
        for ($x = $lat1; $x < $lat2; $x = $this->latPlusMeters($x, $side)) {
            for ($y = $lng1; $y < $lng2; $y = $this->lngPlusMeters($y, $side)) {
                if ($this->isTheMax($x, $y, $side, $radius)) {
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

    private function isTheMax($x, $y, $side, $radius)
    {
        return count($this->getObjects($this->latPlusMeters($x, $side/2), $this->lngPlusMeters($y, $side/2), $radius)['results']) >= config('search')['max_results'];
    }
}
