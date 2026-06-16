<?php

declare(strict_types=1);

namespace App\Domain\MobilePayment\Services;

use App\Domain\MobilePayment\Enums\ActivityItemType;
use App\Domain\MobilePayment\Models\ActivityFeedItem;
use App\Domain\MobilePayment\Models\PaymentIntent;

class ActivityFeedService
{
    /**
     * Get paginated activity feed for a user.
     *
     * @return array<string, mixed>
     */
    public function getFeed(int $userId, ?string $cursor, int $limit = 20, string $filter = 'all'): array
    {
        $limit = min($limit, 50);

        $query = ActivityFeedItem::forUser($userId)
            ->orderByDesc('occurred_at')
            ->orderByDesc('id');

        // Apply filter
        if ($filter === 'income') {
            $query->income();
        } elseif ($filter === 'expenses') {
            $query->expenses();
        }

        // Apply cursor-based pagination
        if ($cursor) {
            $decoded = $this->decodeCursor($cursor);
            if ($decoded) {
                $query->where(function ($q) use ($decoded) {
                    $q->where('occurred_at', '<', $decoded['occurred_at'])
                        ->orWhere(function ($q2) use ($decoded) {
                            $q2->where('occurred_at', '=', $decoded['occurred_at'])
                                ->where('id', '<', $decoded['id']);
                        });
                });
            }
        }

        $items = $query->limit($limit + 1)->get();
        $hasMore = $items->count() > $limit;

        if ($hasMore) {
            $items = $items->take($limit);
        }

        $lastItem = $items->last();
        $nextCursor = $hasMore && $lastItem ? $this->encodeCursor($lastItem) : null;

        return [
            'items'       => $items->map(fn (ActivityFeedItem $item) => $item->toApiResponse())->values()->all(),
            'next_cursor' => $nextCursor,
            'has_more'    => $hasMore,
        ];
    }

    /**
     * Create an activity feed item from a confirmed payment intent.
     */
    public function createFromPaymentIntent(PaymentIntent $intent): ActivityFeedItem
    {
        $intent->loadMissing('merchant');

        return ActivityFeedItem::create([
            'user_id'           => $intent->user_id,
            'activity_type'     => ActivityItemType::MERCHANT_PAYMENT,
            'merchant_name'     => $intent->merchant?->display_name,
            'merchant_icon_url' => $intent->merchant?->icon_url,
            'amount'            => '-' . $intent->amount,
            'asset'             => $intent->asset,
            'network'           => $intent->network,
            'status'            => $intent->status->value,
            'protected'         => $intent->shield_enabled,
            'reference_type'    => PaymentIntent::class,
            'reference_id'      => $intent->id,
            'occurred_at'       => $intent->confirmed_at ?? now(),
        ]);
    }

    private function encodeCursor(ActivityFeedItem $item): string
    {
        return base64_encode(json_encode([
            't'  => $item->occurred_at->toIso8601String(),
            'id' => $item->id,
        ]) ?: '');
    }

    /**
     * @return array<string, string>|null
     */
    private function decodeCursor(string $cursor): ?array
    {
        $decoded = json_decode(base64_decode($cursor, true) ?: '', true);
        if (! is_array($decoded) || ! isset($decoded['t'], $decoded['id'])) {
            return null;
        }

        // Validate date format
        if (! is_string($decoded['t']) || ! strtotime($decoded['t'])) {
            return null;
        }

        // Validate id is a valid UUID string
        if (! is_string($decoded['id']) || strlen($decoded['id']) > 36) {
            return null;
        }

        return [
            'occurred_at' => $decoded['t'],
            'id'          => $decoded['id'],
        ];
    }
}
