<?php

namespace Zoomyboy\LaravelNami;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

class Group implements Arrayable
{
    public string $name;
    public int $id;
    public ?int $parentId;

    public static function fromResponse(array $response, ?int $parent): self
    {
        return (new self())
            ->setName($response['descriptor'])
            ->setId($response['id'])
            ->setParentId($parent);
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function setParentId(?int $parentId = null): self
    {
        $this->parentId = $parentId;

        return $this;
    }

    public function toArray()
    {
        return ['id' => $this->id, 'name' => $this->name, 'parent_id' => $this->parentId];
    }

    public function subgroups()
    {
        return Nami::subgroupsOf($this->id);
    }

    public function fees()
    {
        return Nami::feesOf($this->id);
    }

    public function activities(): Collection
    {
        return Nami::activities($this->id);
    }
}
