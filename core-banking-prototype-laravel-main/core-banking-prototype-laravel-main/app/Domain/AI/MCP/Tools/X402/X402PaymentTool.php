<?php

declare(strict_types=1);

namespace App\Domain\AI\MCP\Tools\X402;

use App\Domain\AI\Contracts\MCPToolInterface;
use App\Domain\AI\ValueObjects\ToolExecutionResult;
use App\Domain\X402\Services\X402ClientService;
use App\Domain\X402\Services\X402HeaderCodecService;
use Exception;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * MCP Tool for handling x402 payments in AI agent workflows.
 *
 * Allows AI agents to:
 * - Detect 402 Payment Required responses
 * - Automatically process x402 payments
 * - Track payment spending and limits
 */
class X402PaymentTool implements MCPToolInterface
{
    public function __construct(
        private readonly X402ClientService $clientService,
        private readonly X402HeaderCodecService $codec,
    ) {
    }

    public function getName(): string
    {
        return 'x402.payment';
    }

    public function getCategory(): string
    {
        return 'x402';
    }

    public function getDescription(): string
    {
        return 'Handle HTTP 402 Payment Required responses by creating signed x402 payment headers for external API calls. '
            . 'Parses payment requirements, enforces spending limits, and returns signed headers for retry.';
    }

    /** @return array<string, mixed> */
    public function getInputSchema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'payment_required_header' => [
                    'type'        => 'string',
                    'description' => 'The Base64-encoded PAYMENT-REQUIRED header from the 402 response.',
                    'minLength'   => 1,
                ],
                'agent_id' => [
                    'type'        => 'string',
                    'description' => 'The agent identifier for spending limit enforcement.',
                    'minLength'   => 1,
                    'maxLength'   => 255,
                ],
            ],
            'required' => ['payment_required_header', 'agent_id'],
        ];
    }

    /** @return array<string, mixed> */
    public function getOutputSchema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'headers' => [
                    'type'        => 'object',
                    'description' => 'Headers to attach to the retry request',
                    'properties'  => [
                        'PAYMENT-SIGNATURE' => ['type' => 'string'],
                    ],
                ],
                'payment' => [
                    'type'       => 'object',
                    'properties' => [
                        'resource'    => ['type' => 'string', 'description' => 'URL of the paid resource'],
                        'description' => ['type' => 'string', 'description' => 'Resource description'],
                        'amount'      => ['type' => 'string', 'description' => 'Payment amount in atomic units'],
                        'network'     => ['type' => 'string', 'description' => 'CAIP-2 network identifier'],
                    ],
                ],
                'instructions' => ['type' => 'string', 'description' => 'How to use the returned headers'],
            ],
        ];
    }

    /** @param array<string, mixed> $parameters */
    public function execute(array $parameters, ?string $conversationId = null): ToolExecutionResult
    {
        try {
            $startTime = microtime(true);

            $paymentRequiredHeader = $parameters['payment_required_header'];
            $agentId = $parameters['agent_id'];

            Log::info('MCP Tool: Processing x402 payment', [
                'agent_id'        => $agentId,
                'conversation_id' => $conversationId,
            ]);

            // Parse the requirements to show what we're paying for
            $requirements = $this->codec->decodePaymentRequired($paymentRequiredHeader);

            // Process the payment and get signed headers
            $headers = $this->clientService->handlePaymentRequired(
                $paymentRequiredHeader,
                $agentId,
            );

            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            $firstAccept = $requirements->accepts[0] ?? null;

            $result = [
                'headers' => $headers,
                'payment' => [
                    'resource'    => $requirements->resource->url,
                    'description' => $requirements->resource->description,
                    'amount'      => $firstAccept->amount ?? 'unknown',
                    'network'     => $firstAccept->network ?? 'unknown',
                ],
                'instructions' => 'Retry the original request with the PAYMENT-SIGNATURE header from the headers field.',
            ];

            Log::info('MCP Tool: x402 payment processed', [
                'agent_id' => $agentId,
                'resource' => $requirements->resource->url,
                'amount'   => $firstAccept->amount ?? 'unknown',
                'network'  => $firstAccept->network ?? 'unknown',
            ]);

            return ToolExecutionResult::success($result, $durationMs);
        } catch (RuntimeException $e) {
            Log::warning('MCP Tool: x402 payment rejected', [
                'agent_id' => $parameters['agent_id'] ?? 'unknown',
                'error'    => $e->getMessage(),
            ]);

            return ToolExecutionResult::failure(
                $e->getMessage() . ' Check spending limits or payment requirements.'
            );
        } catch (Exception $e) {
            Log::error('MCP Tool error: x402.payment', [
                'error'      => $e->getMessage(),
                'parameters' => ['agent_id' => $parameters['agent_id'] ?? 'unknown'],
                'trace'      => $e->getTraceAsString(),
            ]);

            return ToolExecutionResult::failure(
                'An unexpected error occurred processing the x402 payment.'
            );
        }
    }

    /** @return array<int, string> */
    public function getCapabilities(): array
    {
        return [
            'write',
            'payment',
            'x402-protocol',
            'spending-limits',
            'agent-autonomous',
        ];
    }

    public function isCacheable(): bool
    {
        return false; // Payments should never be cached
    }

    public function getCacheTtl(): int
    {
        return 0;
    }

    /** @param array<string, mixed> $parameters */
    public function validateInput(array $parameters): bool
    {
        if (empty($parameters['payment_required_header']) || empty($parameters['agent_id'])) {
            return false;
        }

        // Basic base64 validation
        if (base64_decode($parameters['payment_required_header'], true) === false) {
            return false;
        }

        return true;
    }

    public function authorize(?string $userId): bool
    {
        // x402 payments are initiated by AI agents in autonomous workflows.
        // Authorization is enforced via spending limits in the X402ClientService.
        return true;
    }
}
