<?php

namespace Zoomyboy\LaravelNami\Data;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Attributes\MapInputName;
use Zoomyboy\LaravelNami\Nami;

class Group extends EnumData
{
    public function __construct(
        public int $id,
        #[MapInputName('descriptor')]
        public string $name,
        public ?int $parentId,
    ) {}


    /**
     * @return Collection<int, Activity>
     */
    public function activities(): Collection
    {
        return Nami::activities($this);
    }

    /**
     * @return Collection<int, self>
     */
    public function children(): Collection {
        return Nami::groups($this);
    }
}
