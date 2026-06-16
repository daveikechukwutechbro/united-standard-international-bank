<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Product;

use App\Domain\Product\Models\Product;

class ProductQuery
{
    /**
     * @param  array<string, mixed>  $args
     */
    public function __invoke(mixed $rootValue, array $args): Product
    {
        /** @var Product */
        return Product::findOrFail($args['id']);
    }
}
