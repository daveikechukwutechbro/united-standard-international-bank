<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\AI;

use App\Domain\AI\Services\NaturalLanguageProcessorService;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

final class SendAiMessageMutation
{
    public function __construct(
        private readonly NaturalLanguageProcessorService $nlpService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function __invoke(mixed $rootValue, array $args): array
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        $conversationId = $args['conversation_id'] ?? Str::uuid()->toString();
        $context = isset($args['context']) ? (json_decode($args['context'], true) ?: []) : [];

        $result = $this->nlpService->processQuery(
            $args['message'],
            $context,
        );

        return [
            'conversation_id' => $conversationId,
            'response'        => json_encode($result) ?: '{}',
            'tokens_used'     => null,
        ];
    }
}
