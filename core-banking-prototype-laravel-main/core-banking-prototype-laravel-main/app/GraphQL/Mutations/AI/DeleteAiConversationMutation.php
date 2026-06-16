<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\AI;

use App\Domain\AI\Services\ConversationService;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

final class DeleteAiConversationMutation
{
    public function __construct(
        private readonly ConversationService $conversationService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     */
    public function __invoke(mixed $rootValue, array $args): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        return $this->conversationService->deleteConversation(
            $args['conversation_id'],
            $user->id,
        );
    }
}
