<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Product;

use App\Domain\Product\Models\Product;
use App\Domain\Product\Services\ProductCatalogService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

class CreateProductMutation
{
    public function __construct(
        private readonly ProductCatalogService $productCatalogService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     */
    public function __invoke(mixed $rootValue, array $args): Product
    {
        $user = Auth::user();

        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        $product = $this->productCatalogService->createProduct(
            data: [
                'name'        => $args['name'],
                'description' => $args['description'] ?? '',
                'category'    => $args['category'],
                'type'        => $args['type'],
            ],
            createdBy: (string) $user->id,
        );

        return $product;
    }
}
