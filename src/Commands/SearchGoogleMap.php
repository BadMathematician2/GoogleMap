<?php


namespace GoogleMap\Commands;


use Illuminate\Console\Command;

class SearchGoogleMap extends Command
{
    protected $signature = 'search-in {place}';

    protected $description = 'Search in place and add all objects in DB';

    public function handle()
    {
        $place = config('places')[$this->argument('place')];

        if (isset($place)) {
            foreach ($place as $item) {
                \GoogleMap::searchIn(...$item);
            }
        } else {
            $this->info('Sorry, i can`t find such place in config');
        }

    }

}
