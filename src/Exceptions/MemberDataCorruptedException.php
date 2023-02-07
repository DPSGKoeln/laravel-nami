<?php

namespace Zoomyboy\LaravelNami\Exceptions;

use Exception;

class MemberDataCorruptedException extends Exception implements Skippable
{
    /**
     * @param array<string, mixed> $rawMember
     */
    public function __construct(public array $rawMember)
    {
        parent::__construct('Member data corrupted');
    }
}
