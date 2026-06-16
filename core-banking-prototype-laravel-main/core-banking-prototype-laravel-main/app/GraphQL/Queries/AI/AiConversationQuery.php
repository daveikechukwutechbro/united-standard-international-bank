<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\AI;

use App\Domain\AI\Services\ConversationService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

final class AiConversationQuery
{
    public function __construct(
        private readonly ConversationService $conversationService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>|null
     */
    public function __invoke(mixed $rootValue, array $args): ?array
    {
        $user = Auth::user();

        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        $conversation = $this->conversationService->getConversation($args['conversation_id'], (int) $user->id);

        if (! $conversation) {
            return null;
        }

        return [
            'conversation_id' => $args['conversation_id'],
            'response'        => json_encode($conversation) ?: '{}',
            'tokens_used'     => $conversation['tokens_used'] ?? null,
        ];
    }
}
