<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Wallet\Models\HardwareWalletAssociation;
use App\Domain\Wallet\Models\MultiSigApprovalRequest;
use App\Domain\Wallet\Models\MultiSigSignerApproval;
use App\Domain\Wallet\Models\MultiSigWallet;
use App\Domain\Wallet\Models\MultiSigWalletSigner;
use App\Domain\Wallet\Services\MultiSigApprovalService;
use App\Domain\Wallet\Services\MultiSigWalletService;
use App\Domain\Wallet\ValueObjects\MultiSigConfiguration;
use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: 'Multi-Sig Wallets',
    description: 'Multi-signature wallet management endpoints'
)]
class MultiSigWalletController extends Controller
{
    public function __construct(
        private readonly MultiSigWalletService $walletService,
        private readonly MultiSigApprovalService $approvalService,
    ) {
    }

    /**
     * Create a new multi-signature wallet.
     */
    #[OA\Post(
        path: '/api/multi-sig/wallets',
        summary: 'Create a multi-signature wallet',
        tags: ['Multi-Sig Wallets'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['name', 'chain', 'required_signatures', 'total_signers'], properties: [
        new OA\Property(property: 'name', type: 'string', example: 'Corporate Treasury'),
        new OA\Property(property: 'chain', type: 'string', example: 'ethereum'),
        new OA\Property(property: 'required_signatures', type: 'integer', example: 2),
        new OA\Property(property: 'total_signers', type: 'integer', example: 3),
        ]))
    )]
    #[OA\Response(
        response: 201,
        description: 'Wallet created successfully'
    )]
    #[OA\Response(
        response: 422,
        description: 'Validation error'
    )]
    public function createWallet(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'                => 'required|string|max:100',
            'chain'               => ['required', 'string', Rule::in($this->getSupportedChains())],
            'required_signatures' => 'required|integer|min:1|max:10',
            'total_signers'       => 'required|integer|min:2|max:10',
            'metadata'            => 'array|nullable',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        try {
            /** @var User $user */
            $user = $request->user();

            $config = MultiSigConfiguration::create(
                requiredSignatures: (int) $validated['required_signatures'],
                totalSigners: (int) $validated['total_signers'],
                chain: $validated['chain'],
                name: $validated['name'],
            );

            $wallet = $this->walletService->createWallet(
                owner: $user,
                config: $config,
                metadata: $validated['metadata'] ?? [],
            );

            return response()->json([
                'data'    => $this->formatWallet($wallet),
                'message' => 'Multi-signature wallet created successfully',
            ], 201);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * List user's multi-signature wallets.
     */
    #[OA\Get(
        path: '/api/multi-sig/wallets',
        summary: 'List multi-signature wallets',
        tags: ['Multi-Sig Wallets'],
        parameters: [
        new OA\Parameter(name: 'chain', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ]
    )]
    #[OA\Response(
        response: 200,
        description: 'List of wallets'
    )]
    public function listWallets(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $chain = $request->query('chain');
        $wallets = $this->walletService->getUserWallets(
            $user,
            $chain ? (string) $chain : null,
        );

        return response()->json([
            'data' => $wallets->map(fn ($wallet) => $this->formatWallet($wallet)),
        ]);
    }

    /**
     * Get multi-signature wallet details.
     */
    #[OA\Get(
        path: '/api/multi-sig/wallets/{id}',
        summary: 'Get wallet details',
        tags: ['Multi-Sig Wallets'],
        parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ]
    )]
    #[OA\Response(
        response: 200,
        description: 'Wallet details'
    )]
    #[OA\Response(
        response: 404,
        description: 'Wallet not found'
    )]
    public function getWallet(Request $request, string $id): JsonResponse
    {
        $wallet = $this->walletService->getWalletWithDetails($id);

        if (! $wallet) {
            return response()->json(['error' => 'Wallet not found'], 404);
        }

        /** @var User $user */
        $user = $request->user();

        // Verify user has access
        if (! $this->userHasAccess($user, $wallet)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        return response()->json([
            'data' => $this->formatWalletWithDetails($wallet),
        ]);
    }

    /**
     * Add a signer to a multi-signature wallet.
     */
    #[OA\Post(
        path: '/api/multi-sig/wallets/{id}/signers',
        summary: 'Add a signer to wallet',
        tags: ['Multi-Sig Wallets'],
        parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['signer_type', 'public_key'], properties: [
        new OA\Property(property: 'signer_type', type: 'string', example: 'hardware_ledger'),
        new OA\Property(property: 'public_key', type: 'string'),
        new OA\Property(property: 'address', type: 'string'),
        new OA\Property(property: 'hardware_wallet_association_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'label', type: 'string'),
        ]))
    )]
    #[OA\Response(
        response: 201,
        description: 'Signer added successfully'
    )]
    #[OA\Response(
        response: 404,
        description: 'Wallet not found'
    )]
    public function addSigner(Request $request, string $id): JsonResponse
    {
        $wallet = MultiSigWallet::find($id);

        if (! $wallet) {
            return response()->json(['error' => 'Wallet not found'], 404);
        }

        /** @var User $currentUser */
        $currentUser = $request->user();

        // Only owner can add signers
        if ($wallet->user_id !== $currentUser->id) {
            return response()->json(['error' => 'Only the wallet owner can add signers'], 403);
        }

        $validator = Validator::make($request->all(), [
            'signer_type' => ['required', 'string', Rule::in([
                MultiSigWalletSigner::TYPE_HARDWARE_LEDGER,
                MultiSigWalletSigner::TYPE_HARDWARE_TREZOR,
                MultiSigWalletSigner::TYPE_INTERNAL,
                MultiSigWalletSigner::TYPE_EXTERNAL,
            ])],
            'public_key'                     => 'required|string|min:64',
            'address'                        => 'string|nullable',
            'hardware_wallet_association_id' => 'uuid|nullable|exists:hardware_wallet_associations,id',
            'label'                          => 'string|max:100|nullable',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        try {
            $hardwareWallet = null;
            if (! empty($validated['hardware_wallet_association_id'])) {
                /** @var HardwareWalletAssociation|null $foundHardwareWallet */
                $foundHardwareWallet = HardwareWalletAssociation::find($validated['hardware_wallet_association_id']);
                $hardwareWallet = $foundHardwareWallet;
            }

            $signer = $this->walletService->addSigner(
                wallet: $wallet,
                signerType: $validated['signer_type'],
                publicKey: $validated['public_key'],
                address: $validated['address'] ?? null,
                user: $currentUser,
                hardwareWallet: $hardwareWallet,
                label: $validated['label'] ?? null,
            );

            return response()->json([
                'data'    => $this->formatSigner($signer),
                'message' => 'Signer added successfully',
            ], 201);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Create an approval request.
     */
    #[OA\Post(
        path: '/api/multi-sig/wallets/{id}/approval-requests',
        summary: 'Create an approval request',
        tags: ['Multi-Sig Wallets'],
        parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['transaction_data'], properties: [
        new OA\Property(property: 'transaction_data', type: 'object'),
        new OA\Property(property: 'request_type', type: 'string', example: 'transaction'),
        ]))
    )]
    #[OA\Response(
        response: 201,
        description: 'Approval request created'
    )]
    #[OA\Response(
        response: 404,
        description: 'Wallet not found'
    )]
    public function createApprovalRequest(Request $request, string $id): JsonResponse
    {
        $wallet = MultiSigWallet::find($id);

        if (! $wallet) {
            return response()->json(['error' => 'Wallet not found'], 404);
        }

        /** @var User $currentUser */
        $currentUser = $request->user();

        if (! $this->userHasAccess($currentUser, $wallet)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $validator = Validator::make($request->all(), [
            'transaction_data'        => 'required|array',
            'transaction_data.to'     => 'required|string',
            'transaction_data.amount' => 'required|numeric|min:0',
            'request_type'            => ['string', Rule::in([
                MultiSigApprovalRequest::TYPE_TRANSACTION,
                MultiSigApprovalRequest::TYPE_CONFIG_CHANGE,
            ])],
            'metadata' => 'array|nullable',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        try {
            $approvalRequest = $this->approvalService->createApprovalRequest(
                wallet: $wallet,
                initiator: $currentUser,
                transactionData: $validated['transaction_data'],
                requestType: $validated['request_type'] ?? MultiSigApprovalRequest::TYPE_TRANSACTION,
                metadata: $validated['metadata'] ?? [],
            );

            return response()->json([
                'data'    => $this->formatApprovalRequest($approvalRequest),
                'message' => 'Approval request created successfully',
            ], 201);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Submit a signature for an approval request.
     */
    #[OA\Post(
        path: '/api/multi-sig/approval-requests/{id}/approve',
        summary: 'Submit signature for approval',
        tags: ['Multi-Sig Wallets'],
        parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['signature', 'public_key'], properties: [
        new OA\Property(property: 'signature', type: 'string'),
        new OA\Property(property: 'public_key', type: 'string'),
        ]))
    )]
    #[OA\Response(
        response: 200,
        description: 'Signature submitted'
    )]
    #[OA\Response(
        response: 404,
        description: 'Request not found'
    )]
    public function submitApproval(Request $request, string $id): JsonResponse
    {
        $approvalRequest = MultiSigApprovalRequest::with('wallet')->find($id);

        if (! $approvalRequest) {
            return response()->json(['error' => 'Approval request not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'signature'  => 'required|string|min:64',
            'public_key' => 'required|string|min:64',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        try {
            /** @var User $currentUser */
            $currentUser = $request->user();

            $signerApproval = $this->approvalService->submitSignature(
                request: $approvalRequest,
                user: $currentUser,
                signature: $validated['signature'],
                publicKey: $validated['public_key'],
            );

            $approvalRequest->refresh();
            $status = $this->approvalService->getApprovalStatus($approvalRequest);

            return response()->json([
                'data' => [
                    'signer_approval' => $this->formatSignerApproval($signerApproval),
                    'request_status'  => $status->toArray(),
                ],
                'message' => $status->isApproved()
                    ? 'Quorum reached! Transaction ready for broadcast.'
                    : 'Signature submitted successfully',
            ]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Reject an approval request.
     */
    #[OA\Post(
        path: '/api/multi-sig/approval-requests/{id}/reject',
        summary: 'Reject an approval request',
        tags: ['Multi-Sig Wallets'],
        parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(properties: [
        new OA\Property(property: 'reason', type: 'string'),
        ]))
    )]
    #[OA\Response(
        response: 200,
        description: 'Request rejected'
    )]
    #[OA\Response(
        response: 404,
        description: 'Request not found'
    )]
    public function rejectApproval(Request $request, string $id): JsonResponse
    {
        $approvalRequest = MultiSigApprovalRequest::with('wallet')->find($id);

        if (! $approvalRequest) {
            return response()->json(['error' => 'Approval request not found'], 404);
        }

        try {
            /** @var User $currentUser */
            $currentUser = $request->user();

            $signerApproval = $this->approvalService->rejectRequest(
                request: $approvalRequest,
                user: $currentUser,
                reason: $request->input('reason'),
            );

            return response()->json([
                'data'    => $this->formatSignerApproval($signerApproval),
                'message' => 'Request rejected',
            ]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Broadcast a fully-signed transaction.
     */
    #[OA\Post(
        path: '/api/multi-sig/approval-requests/{id}/broadcast',
        summary: 'Broadcast the signed transaction',
        tags: ['Multi-Sig Wallets'],
        parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ]
    )]
    #[OA\Response(
        response: 200,
        description: 'Transaction broadcast'
    )]
    #[OA\Response(
        response: 404,
        description: 'Request not found'
    )]
    public function broadcastTransaction(Request $request, string $id): JsonResponse
    {
        $approvalRequest = MultiSigApprovalRequest::with('wallet')->find($id);

        if (! $approvalRequest) {
            return response()->json(['error' => 'Approval request not found'], 404);
        }

        /** @var User $currentUser */
        $currentUser = $request->user();

        // Only owner or initiator can broadcast
        $wallet = $approvalRequest->wallet;
        if ($wallet->user_id !== $currentUser->id && $approvalRequest->initiator_user_id !== $currentUser->id) {
            return response()->json(['error' => 'Only the wallet owner or initiator can broadcast'], 403);
        }

        try {
            $this->approvalService->broadcastTransaction($approvalRequest);
            $approvalRequest->refresh();

            return response()->json([
                'data' => [
                    'transaction_hash' => $approvalRequest->transaction_hash,
                    'status'           => $approvalRequest->status,
                ],
                'message' => 'Transaction broadcast successfully',
            ]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Get supported configuration.
     */
    #[OA\Get(
        path: '/api/multi-sig/supported',
        summary: 'Get supported multi-sig configuration',
        tags: ['Multi-Sig Wallets']
    )]
    #[OA\Response(
        response: 200,
        description: 'Supported configuration'
    )]
    public function getSupported(): JsonResponse
    {
        return response()->json([
            'data' => [
                'enabled'           => $this->walletService->isMultiSigEnabled(),
                'supported_schemes' => $this->walletService->getSupportedSchemes(),
                'supported_chains'  => $this->getSupportedChains(),
                'limits'            => $this->walletService->getConfigurationLimits(),
            ],
        ]);
    }

    /**
     * Get pending approval requests for current user.
     */
    #[OA\Get(
        path: '/api/multi-sig/pending-approvals',
        summary: 'Get pending approval requests',
        tags: ['Multi-Sig Wallets']
    )]
    #[OA\Response(
        response: 200,
        description: 'List of pending approvals'
    )]
    public function getPendingApprovals(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $pendingRequests = $this->approvalService->getPendingRequestsForUser($user);

        return response()->json([
            'data' => $pendingRequests->map(fn ($req) => $this->formatApprovalRequest($req)),
        ]);
    }

    /**
     * Get supported chains.
     *
     * @return list<string>
     */
    private function getSupportedChains(): array
    {
        /** @var array<string, mixed> $chains */
        $chains = config('blockchain.hardware_wallets.supported_chains', []);

        return array_keys($chains);
    }

    /**
     * Check if user has access to wallet.
     */
    private function userHasAccess(User $user, MultiSigWallet $wallet): bool
    {
        return $wallet->user_id === $user->id || $wallet->isUserSigner($user->id);
    }

    /**
     * Format wallet for response.
     *
     * @return array<string, mixed>
     */
    private function formatWallet(MultiSigWallet $wallet): array
    {
        return [
            'id'                  => $wallet->id,
            'name'                => $wallet->name,
            'address'             => $wallet->address,
            'chain'               => $wallet->chain,
            'scheme'              => $wallet->getSchemeDescription(),
            'required_signatures' => $wallet->required_signatures,
            'total_signers'       => $wallet->total_signers,
            'active_signers'      => $wallet->activeSigners()->count(),
            'status'              => $wallet->status,
            'is_owner'            => $wallet->user_id === auth()->id(),
            'created_at'          => $wallet->created_at->toIso8601String(),
        ];
    }

    /**
     * Format wallet with full details.
     *
     * @return array<string, mixed>
     */
    private function formatWalletWithDetails(MultiSigWallet $wallet): array
    {
        return array_merge($this->formatWallet($wallet), [
            'owner' => [
                'id'   => $wallet->user->id,
                'name' => $wallet->user->name,
            ],
            'signers'          => $wallet->signers->map(fn ($s) => $this->formatSigner($s)),
            'pending_requests' => $wallet->pendingApprovalRequests->count(),
            'metadata'         => $wallet->metadata,
        ]);
    }

    /**
     * Format signer for response.
     *
     * @return array<string, mixed>
     */
    private function formatSigner(MultiSigWalletSigner $signer): array
    {
        return [
            'id'           => $signer->id,
            'signer_type'  => $signer->signer_type,
            'label'        => $signer->getDisplayName(),
            'address'      => $signer->address,
            'signer_order' => $signer->signer_order,
            'is_active'    => $signer->is_active,
            'user'         => $signer->user ? [
                'id'   => $signer->user->id,
                'name' => $signer->user->name,
            ] : null,
            'is_hardware_wallet' => $signer->isHardwareWallet(),
        ];
    }

    /**
     * Format approval request for response.
     *
     * @return array<string, mixed>
     */
    private function formatApprovalRequest(MultiSigApprovalRequest $request): array
    {
        return [
            'id'                  => $request->id,
            'wallet_id'           => $request->multi_sig_wallet_id,
            'wallet_name'         => $request->wallet->name ?? null,
            'status'              => $request->status,
            'request_type'        => $request->request_type,
            'transaction_data'    => $request->transaction_data,
            'required_signatures' => $request->required_signatures,
            'current_signatures'  => $request->current_signatures,
            'remaining'           => $request->getRemainingSignaturesCount(),
            'quorum_reached'      => $request->hasReachedQuorum(),
            'initiator'           => $request->initiator ? [
                'id'   => $request->initiator->id,
                'name' => $request->initiator->name,
            ] : null,
            'transaction_hash' => $request->transaction_hash,
            'expires_at'       => $request->expires_at->toIso8601String(),
            'is_expired'       => $request->isExpired(),
            'created_at'       => $request->created_at->toIso8601String(),
        ];
    }

    /**
     * Format signer approval for response.
     *
     * @return array<string, mixed>
     */
    private function formatSignerApproval(MultiSigSignerApproval $approval): array
    {
        return [
            'id'          => $approval->id,
            'signer_id'   => $approval->signer_id,
            'signer_name' => $approval->signer->getDisplayName(),
            'decision'    => $approval->decision,
            'decided_at'  => $approval->decided_at?->toIso8601String(),
        ];
    }
}
