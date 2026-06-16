<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Basket;

use App\Domain\Basket\Models\BasketAsset;

class BasketQuery
{
    /**
     * @param  array<string, mixed>  $args
     */
    public function __invoke(mixed $rootValue, array $args): BasketAsset
    {
        /** @var BasketAsset */
        return BasketAsset::findOrFail($args['id']);
    }
}
