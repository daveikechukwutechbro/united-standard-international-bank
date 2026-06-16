<?php

declare(strict_types=1);

namespace App\GraphQL\Directives;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Auth\Access\AuthorizationException;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Schema\Values\FieldValue;
use Nuwave\Lighthouse\Support\Contracts\FieldMiddleware;

/**
 * Custom @tenant directive for multi-tenant scoping in GraphQL queries.
 *
 * Ensures the query is scoped to the current tenant context.
 */
class TenantDirective extends BaseDirective implements FieldMiddleware
{
    public static function definition(): string
    {
        return /** @lang GraphQL */ <<<'GRAPHQL'
"Apply tenant scoping to a field or query."
directive @tenant on FIELD_DEFINITION
GRAPHQL;
    }

    public function handleField(FieldValue $fieldValue): void
    {
        $fieldValue->wrapResolver(fn (callable $resolver): Closure => function (mixed $root, array $args, mixed $context, ResolveInfo $resolveInfo) use ($resolver) {
            // If tenancy is active, the scoping is handled by the tenant connection
            if (function_exists('tenant') && tenant()) {
                return $resolver($root, $args, $context, $resolveInfo);
            }

            throw new AuthorizationException('Tenant context is required for this operation.');
        });
    }
}
