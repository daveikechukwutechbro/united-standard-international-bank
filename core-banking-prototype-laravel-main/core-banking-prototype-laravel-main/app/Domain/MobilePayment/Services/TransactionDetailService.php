<?php

declare(strict_types=1);

namespace App\Domain\MobilePayment\Services;

use App\Domain\MobilePayment\Models\ActivityFeedItem;

class TransactionDetailService
{
    /**
     * Get full transaction details for a given activity feed item.
     *
     * @return array<string, mixed>
     */
    public function getDetails(string $txId, int $userId): ?array
    {
        $item = ActivityFeedItem::where('id', $txId)
            ->where('user_id', $userId)
            ->first();

        if (! $item) {
            return null;
        }

        $response = [
            'id'          => $item->id,
            'type'        => $item->activity_type->value,
            'amount'      => $item->amount,
            'asset'       => $item->asset,
            'network'     => $item->network,
            'timestamp'   => $item->occurred_at->toIso8601String(),
            'referenceId' => $this->formatReferenceId($item),
            'status'      => $item->status,
            'protected'   => $item->protected,
        ];

        if ($item->merchant_name) {
            $response['merchantName'] = $item->merchant_name;
            $response['merchantIconUrl'] = $item->merchant_icon_url;
        }

        if ($item->from_address) {
            $response['fromAddress'] = $item->from_address;
        }

        if ($item->to_address) {
            $response['toAddress'] = $item->to_address;
        }

        // Fee info from the referenced payment intent
        $reference = $item->reference;
        if ($reference && method_exists($reference, 'getAttribute')) {
            $feesEstimate = $reference->getAttribute('fees_estimate');
            if ($feesEstimate) {
                $response['fee'] = $feesEstimate;
            }

            $txHash = $reference->getAttribute('tx_hash');
            if ($txHash) {
                $response['explorerUrl'] = $reference->getAttribute('tx_explorer_url');
            }
        }

        $response['privacyNote'] = $item->protected
            ? 'Privacy-preserving by default. Additional disclosure available when legally required.'
            : null;

        return $response;
    }

    private function formatReferenceId(ActivityFeedItem $item): string
    {
        return '#' . strtoupper(substr(str_replace('-', '', $item->id), 0, 8));
    }
}
