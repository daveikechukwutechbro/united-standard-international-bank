<?php

namespace Tests\Unit\Testing\Support;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class SimpleTestEvent extends ShouldBeStored
{
    public string $name;

    public int $value;
}
