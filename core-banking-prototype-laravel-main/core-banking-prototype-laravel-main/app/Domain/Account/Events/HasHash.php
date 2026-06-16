<?php

namespace App\Domain\Account\Events;

use App\Domain\Account\DataObjects\Hash;

interface HasHash
{
    public function getHash(): Hash;
}
