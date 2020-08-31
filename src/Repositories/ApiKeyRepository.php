<?php


namespace GoogleMap\Repositories;


use GoogleMap\Repositories\Interfaces\ApiKeyRepositoryInterface;
use Carbon\Carbon;
use GoogleMap\Models\ApiKey;

class ApiKeyRepository implements ApiKeyRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function addKey(string $api_key)
    {
        ApiKey::query()->create(['api_key' => $api_key, 'status' => true]);
    }

    public function updateStatuses()
    {
        $inactive = ApiKey::query()->where('status', false)->get();

        $now = Carbon::now();
        foreach ($inactive as $key) {
            if ($now->diff($key->updated_at)->days >= config('api_key')['days_inactive'] ) {
                $key->update(['status' => true]);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function changeApiKey(string $api_key)
    {
        $this->makeInActive($api_key);

        return $this->takeApiKey();
    }

    /**
     * {@inheritDoc}
     */
    public function makeInActive(string $api_key)
    {
        ApiKey::query()->where('api_key', $api_key)->update(['status' => false]);
    }

    public function takeApiKey()
    {
        return ApiKey::query()->firstWhere('status', true)->getAttribute('api_key');
    }


}
