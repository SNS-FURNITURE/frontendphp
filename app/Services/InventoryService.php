<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Apply a stock movement and upsert stock_levels (Express parity; no negative check).
     *
     * @param  array{item_id:mixed,movement_type:string,quantity:mixed,warehouse?:string,reference_type?:mixed,reference_id?:mixed}  $payload
     */
    public function applyMovement(array $payload, ?User $user = null): void
    {
        $itemId = $payload['item_id'];
        $movementType = (string) $payload['movement_type'];
        $quantity = $payload['quantity'];
        $warehouse = $payload['warehouse'] ?? 'Main Warehouse';
        $referenceType = $payload['reference_type'] ?? null;
        $referenceId = $payload['reference_id'] ?? null;

        DB::transaction(function () use ($itemId, $movementType, $quantity, $warehouse, $referenceType, $referenceId, $user) {
            DB::table('stock_movements')->insert([
                'item_id' => $itemId,
                'movement_type' => $movementType,
                'quantity' => $quantity,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'created_by' => $user?->id,
                'created_at' => now(),
            ]);

            $qtyDelta = $movementType === 'out'
                ? -abs((float) $quantity)
                : abs((float) $quantity);

            DB::statement(
                'INSERT INTO stock_levels (item_id, warehouse, quantity_on_hand)
                 VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE quantity_on_hand = quantity_on_hand + ?',
                [$itemId, $warehouse, $qtyDelta > 0 ? $qtyDelta : 0, $qtyDelta],
            );
        });
    }
}
