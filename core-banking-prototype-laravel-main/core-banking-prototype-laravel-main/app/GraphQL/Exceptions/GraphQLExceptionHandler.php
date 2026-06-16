<?php

declare(strict_types=1);

namespace App\GraphQL\Exceptions;

use Closure;
use GraphQL\Error\Error;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Nuwave\Lighthouse\Execution\ErrorHandler;

class GraphQLExceptionHandler implements ErrorHandler
{
    public function __invoke(?Error $error, Closure $next): ?array
    {
        if ($error === null) {
            return $next(null);
        }

        $previous = $error->getPrevious();

        if ($previous instanceof AuthenticationException) {
            return $next(new Error(
                'Unauthenticated.',
                $error->getNodes(),
                $error->getSource(),
                $error->getPositions(),
                $error->getPath(),
                $previous,
                ['category' => 'authentication'],
            ));
        }

        if ($previous instanceof ModelNotFoundException) {
            return $next(new Error(
                'Resource not found.',
                $error->getNodes(),
                $error->getSource(),
                $error->getPositions(),
                $error->getPath(),
                $previous,
                ['category' => 'not_found'],
            ));
        }

        if ($previous instanceof ValidationException) {
            return $next(new Error(
                $previous->getMessage(),
                $error->getNodes(),
                $error->getSource(),
                $error->getPositions(),
                $error->getPath(),
                $previous,
                [
                    'category'   => 'validation',
                    'validation' => $previous->errors(),
                ],
            ));
        }

        return $next($error);
    }
}
