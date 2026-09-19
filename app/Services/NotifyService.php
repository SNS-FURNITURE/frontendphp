<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Throwable;

class NotifyService
{
    /**
     * @param  array{type: string, title: string, message: string, entityType?: string|null, entityId?: int|null}  $payload
     */
    public function notifyUser(int $userId, array $payload): void
    {
        try {
            Notification::query()->create([
                'user_id' => $userId,
                'type' => $payload['type'],
                'title' => $payload['title'],
                'message' => $payload['message'],
                'entity_type' => $payload['entityType'] ?? null,
                'entity_id' => $payload['entityId'] ?? null,
                'is_read' => false,
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * @param  list<string>  $roleNames
     * @return list<int>
     */
    public function activeUserIdsWithRoles(array $roleNames): array
    {
        return User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($q) => $q->whereIn('name', $roleNames))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Fallback when Eloquent role relation naming differs — match Express JOIN.
     *
     * @param  list<string>  $roleNames
     * @return list<int>
     */
    public function activeUserIdsWithRolesRaw(array $roleNames): array
    {
        $rows = DB::table('users as u')
            ->join('user_roles as ur', 'ur.user_id', '=', 'u.id')
            ->join('roles as r', 'r.id', '=', 'ur.role_id')
            ->whereIn('r.name', $roleNames)
            ->where('u.is_active', true)
            ->distinct()
            ->pluck('u.id');

        return $rows->map(fn ($id) => (int) $id)->all();
    }

    /**
     * @return array{id:int, full_name:string, email?:string}|null
     */
    public function findActiveUserByRole(string $roleName): ?array
    {
        $row = DB::table('users as u')
            ->join('user_roles as ur', 'ur.user_id', '=', 'u.id')
            ->join('roles as r', 'r.id', '=', 'ur.role_id')
            ->where('r.name', $roleName)
            ->where('u.is_active', true)
            ->select('u.id', 'u.full_name', 'u.email')
            ->first();

        if (! $row) {
            return null;
        }

        return [
            'id' => (int) $row->id,
            'full_name' => (string) $row->full_name,
            'email' => $row->email ?? null,
        ];
    }
}
