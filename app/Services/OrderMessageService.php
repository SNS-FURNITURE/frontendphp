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

    public function markRead(OrderIntake $intake, User $actor): int
    {
        if (! $actor->canMessageOpsPeer()) {
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
