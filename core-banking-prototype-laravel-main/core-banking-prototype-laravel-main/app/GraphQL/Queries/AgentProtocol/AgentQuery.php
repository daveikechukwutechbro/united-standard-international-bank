<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\AgentProtocol;

use App\Domain\AgentProtocol\Models\Agent;

class AgentQuery
{
    /**
     * @param  array<string, mixed>  $args
     */
    public function __invoke(mixed $rootValue, array $args): Agent
    {
        /** @var Agent */
        return Agent::findOrFail($args['id']);
    }
}
