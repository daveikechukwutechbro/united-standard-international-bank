<?php

namespace Tests\Unit\Testing\Support;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class EventWithPrivateProperties extends ShouldBeStored
{
    private string $privateData;

    public string $publicData;

    public function getPrivateData(): string
    {
        return $this->privateData;
    }
}
