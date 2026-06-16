<?php

declare(strict_types=1);

namespace App\Domain\AI\Services;

class AIAgentService
{
    public function __construct(
        private readonly AgentOrchestratorService $orchestrator,
    ) {
    }

    /**
     * Send a chat message to the AI agent.
     */
    public function chat(
        string $message,
        string $conversationId,
        int $userId,
        array $context = [],
        array $options = []
    ): array {
        $response = $this->orchestrator->process($message, $context);

        return [
            'message_id' => $response['message_id'],
            'content'    => $response['content'],
            'confidence' => $response['confidence'],
            'tools_used' => $response['tools_used'],
            'context'    => $context,
        ];
    }

    /**
     * Store user feedback about an AI response.
     */
    public function storeFeedback(
        string $messageId,
        int $userId,
        int $rating,
        ?string $feedback = null
    ): void {
        // Store feedback for future model improvements
        // In production, this would save to database or analytics service
    }
}
