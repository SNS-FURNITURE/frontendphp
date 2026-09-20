<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuditService
{
    public function log(
        ?User $user,
        string $entityType,
        int $entityId,
        string $action,
        ?array $changes = null,
        ?Request $request = null,
    ): void {
        try {
            AuditLog::query()->create([
                'user_id' => $user?->id,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'action' => $action,
                'changes_json' => $changes,
                'ip_address' => $request?->ip() ?? '127.0.0.1',
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
