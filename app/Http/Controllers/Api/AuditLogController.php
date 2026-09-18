<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = AuditLog::query()
            ->with('user:id,full_name,email')
            ->orderByDesc('created_at')
            ->limit(100);

        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->string('entity_type'));
        }
        if ($request->filled('entity_id')) {
            $query->where('entity_id', (int) $request->input('entity_id'));
        }

        $rows = $query->get()->map(function (AuditLog $row) {
            return [
                'id' => (int) $row->id,
                'user_id' => $row->user_id ? (int) $row->user_id : null,
                'entity_type' => $row->entity_type,
                'entity_id' => (int) $row->entity_id,
                'action' => $row->action,
                'changes_json' => $row->changes_json,
                'created_at' => $row->created_at,
                'user' => $row->user ? [
                    'id' => (int) $row->user->id,
                    'full_name' => $row->user->full_name,
                    'email' => $row->user->email,
                ] : null,
            ];
        });

        return ApiResponse::success($rows);
    }
}
