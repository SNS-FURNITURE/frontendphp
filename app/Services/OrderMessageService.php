<?php

namespace App\Services;

use App\Models\OrderIntake;
use App\Models\OrderMessage;
use App\Models\User;
use App\Support\OrderOperations;
use Illuminate\Http\Request;
use InvalidArgumentException;

class OrderMessageService
{
    public function __construct(
        private AuditService $audit,
        private NotifyService $notify,
    ) {}

    public function send(OrderIntake $intake, User $actor, string $body, ?Request $request = null): OrderMessage
    {
        if (! $actor->canMessageOpsPeer()) {
            throw new InvalidArgumentException('Only OMS and OMF can use this channel.');
        }

        $body = trim($body);
        if ($body === '') {
            throw new InvalidArgumentException('Message body is required.');
        }

        $message = OrderMessage::query()->create([
            'order_intake_id' => $intake->id,
            'sender_id' => $actor->id,
            'body' => $body,
            'message_kind' => OrderOperations::MESSAGE_OPS,
            'is_read' => false,
        ]);

        $recipientRoles = $actor->isOms()
            ? [OrderOperations::ROLE_OMF]
            : [OrderOperations::ROLE_OMS];

        foreach ($this->notify->activeUserIdsWithRoles($recipientRoles) as $userId) {
            if ($userId === (int) $actor->id) {
                continue;
            }
            $this->notify->notifyUser($userId, [
                'type' => 'ops_message',
                'title' => 'OMS ↔ OMF message',
                'message' => "New message on {$intake->invoice_number}: ".mb_strimwidth($body, 0, 80, '…'),
                'entityType' => 'order_intake',
                'entityId' => (int) $intake->id,
            ]);
        }

        $this->audit->log($actor, 'order_message', (int) $message->id, 'SEND_OPS_MESSAGE', [
            'order_intake_id' => $intake->id,
        ], $request);

        return $message;
    }

    /**
     * Product manager production update — visible to both OMS and OMF.
     */
    public function postProductionUpdate(OrderIntake $intake, User $actor, string $body, ?Request $request = null): OrderMessage
    {
        if (! $actor->canPostProductionUpdate()) {
            throw new InvalidArgumentException('Only the product manager can post production updates.');
        }

        if ($intake->status !== OrderOperations::INTAKE_ACCEPTED) {
            throw new InvalidArgumentException('Production updates are only for accepted orders.');
        }

        $isAssigned = $intake->assignments()
            ->where('role_key', OrderOperations::ROLE_PRODUCT_MANAGER)
            ->where('user_id', $actor->id)
            ->exists();

        if (! $isAssigned && ! $actor->isAdmin()) {
            throw new InvalidArgumentException('You are not the product manager for this order.');
        }

        $body = trim($body);
        if ($body === '') {
            throw new InvalidArgumentException('Update text is required.');
        }

        $message = OrderMessage::query()->create([
            'order_intake_id' => $intake->id,
            'sender_id' => $actor->id,
            'body' => $body,
            'message_kind' => OrderOperations::MESSAGE_PRODUCTION_UPDATE,
            'is_read' => false,
        ]);

        foreach ($this->notify->activeUserIdsWithRoles([
            OrderOperations::ROLE_OMS,
            OrderOperations::ROLE_OMF,
        ]) as $userId) {
            if ($userId === (int) $actor->id) {
                continue;
            }
            $this->notify->notifyUser($userId, [
                'type' => 'production_update',
                'title' => 'Production update',
                'message' => "{$actor->full_name} on {$intake->invoice_number}: ".mb_strimwidth($body, 0, 100, '…'),
                'entityType' => 'order_intake',
                'entityId' => (int) $intake->id,
            ]);
        }

        $this->audit->log($actor, 'order_message', (int) $message->id, 'POST_PRODUCTION_UPDATE', [
            'order_intake_id' => $intake->id,
        ], $request);

        return $message;
    }

    public function markRead(OrderIntake $intake, User $actor): int
    {
        if (! $actor->canMessageOpsPeer() && ! $actor->isProductManager()) {
            return 0;
        }

        return OrderMessage::query()
            ->where('order_intake_id', $intake->id)
            ->where('sender_id', '!=', $actor->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }
}
