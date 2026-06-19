<?php

namespace App\Services;

use App\Models\Location;

class LocationService
{
    public function list()
    {
        return Location::latest()->paginate(20);
    }

    public function create(array $data)
    {
        return Location::create($data);
    }

    public function update(Location $location, array $data)
    {
        $location->update($data);
        return $location;
    }

    public function delete(Location $location)
    {
        return $location->delete();
    }
}
