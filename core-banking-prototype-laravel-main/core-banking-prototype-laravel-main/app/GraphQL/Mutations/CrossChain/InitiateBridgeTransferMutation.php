<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\CrossChain;

use App\Domain\CrossChain\Enums\CrossChainNetwork;
use App\Domain\CrossChain\Models\BridgeTransaction;
use App\Domain\CrossChain\Services\BridgeOrchestratorService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use Throwable;

class InitiateBridgeTransferMutation
{
    public function __construct(
        private readonly BridgeOrchestratorService $bridgeService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     */
    public function __invoke(mixed $rootValue, array $args): BridgeTransaction
    {
        $user = Auth::user();

        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        $sourceChain = CrossChainNetwork::from($args['source_chain']);
        $destChain = CrossChainNetwork::from($args['dest_chain']);

        try {
            $quote = $this->bridgeService->getBestQuote(
                sourceChain: $sourceChain,
                destChain: $destChain,
                token: $args['token'],
                amount: $args['amount'],
            );

            $result = $this->bridgeService->initiateBridge(
                quoteId: $quote->quoteId,
                senderAddress: $args['sender_address'] ?? '',
                recipientAddress: $args['recipient_address'],
                userUuid: $user->uuid,
            );

            return BridgeTransaction::create([
                'user_id'           => $user->id,
                'transaction_id'    => $result['transaction_id'],
                'source_chain'      => $args['source_chain'],
                'dest_chain'        => $args['dest_chain'],
                'token'             => $args['token'],
                'amount'            => $args['amount'],
                'recipient_address' => $args['recipient_address'],
                'provider'          => $quote->getProvider()->value,
                'status'            => $result['status']->value ?? 'pending',
            ]);
        } catch (Throwable $e) {
            // Fallback: create a pending transaction record for tracking.
            return BridgeTransaction::create([
                'user_id'           => $user->id,
                'source_chain'      => $args['source_chain'],
                'dest_chain'        => $args['dest_chain'],
                'token'             => $args['token'],
                'amount'            => $args['amount'],
                'recipient_address' => $args['recipient_address'],
                'provider'          => $args['provider'] ?? 'wormhole',
                'status'            => 'pending',
            ]);
        }
    }
}
