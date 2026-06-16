<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\ISO20022;

use App\Domain\ISO20022\Services\MessageValidator;

final class ValidateMessageMutation
{
    public function __construct(
        private readonly MessageValidator $validator,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array{valid: bool, errors: array<string>}
     */
    public function __invoke(mixed $rootValue, array $args): array
    {
        return $this->validator->validate($args['xml']);
    }
}
