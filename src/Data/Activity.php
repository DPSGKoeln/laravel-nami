<?php

namespace Zoomyboy\LaravelNami\Data;

use Illuminate\Support\Collection;
use Zoomyboy\LaravelNami\Nami;

class Activity extends EnumData
{
    /**
     * @return Collection<int, Subactivity>
     */
    public function subactivities(): Collection
    {
        return Nami::subactivitiesOf($this);
    }
}
