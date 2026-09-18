<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Board;
use App\Models\BoardColumn;
use App\Models\BoardGroup;
use App\Models\BoardItem;
use App\Models\BoardItemValue;
use App\Models\User;
use App\Models\Workspace;
use App\Services\AuditService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BoardController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function workspacesIndex(): JsonResponse
    {
        return ApiResponse::success(
            Workspace::query()->orderByDesc('created_at')->get()->values()->all()
        );
    }

    public function workspacesStore(Request $request): JsonResponse
    {
        $name = $request->input('name');
        if (! $name) {
            return ApiResponse::error('Workspace name is required', 'INVALID_INPUT', 400);
        }

        $ws = Workspace::query()->create([
            'name' => $name,
            'visibility' => $request->input('visibility') ?: 'PRIVATE',
        ]);

        return ApiResponse::success([
            'id' => (int) $ws->id,
            'name' => $ws->name,
            'visibility' => $ws->visibility,
        ], null, 201);
    }

    public function boardsIndex(Request $request): JsonResponse
    {
        $query = Board::query()->where('is_active', true)->orderByDesc('created_at');
        if ($ws = $request->query('workspace_id')) {
            $query->where('workspace_id', $ws);
        }

        return ApiResponse::success($query->get()->values()->all());
    }

    public function boardsStore(Request $request): JsonResponse
    {
        $workspaceId = $request->input('workspace_id');
        $name = $request->input('name');

        if (! $workspaceId || ! $name) {
            return ApiResponse::error('workspace_id and name are required', 'INVALID_INPUT', 400);
        }

        /** @var User|null $user */
        $user = $request->attributes->get('auth_user') ?? $request->user();

        $result = DB::transaction(function () use ($request, $workspaceId, $name, $user) {
            $board = Board::query()->create([
                'workspace_id' => $workspaceId,
                'name' => $name,
                'board_type' => $request->input('board_type') ?: 'main',
                'module_key' => $request->input('module_key'),
                'is_active' => true,
                'created_by_user_id' => $user?->id,
            ]);

            $group = BoardGroup::query()->create([
                'board_id' => $board->id,
                'name' => 'New Items',
                'sort_order' => 1,
            ]);

            BoardColumn::query()->create([
                'board_id' => $board->id,
                'name' => 'Status',
                'key_name' => 'status',
                'column_type' => 'status',
                'sort_order' => 1,
            ]);
            BoardColumn::query()->create([
                'board_id' => $board->id,
                'name' => 'Owner',
                'key_name' => 'owner',
                'column_type' => 'people',
                'sort_order' => 2,
            ]);

            return [$board, $group];
        });

        [$board, $group] = $result;

        $this->audit->log($user, 'board', (int) $board->id, 'CREATE_BOARD', [
            'name' => $name,
            'workspace_id' => $workspaceId,
        ], $request);

        return ApiResponse::success([
            'id' => (int) $board->id,
            'workspace_id' => (int) $workspaceId,
            'name' => $name,
            'default_group_id' => (int) $group->id,
        ], null, 201);
    }

    public function boardsShow(int $id): JsonResponse
    {
        $board = Board::query()->find($id);
        if (! $board) {
            return ApiResponse::error('Board not found', 'NOT_FOUND', 404);
        }

        return ApiResponse::success(array_merge($board->toArray(), [
            'groups' => $board->groups()->get()->values()->all(),
            'columns' => $board->columns()->get()->values()->all(),
            'items' => $board->items()->get()->values()->all(),
        ]));
    }

    public function storeGroup(Request $request, int $id): JsonResponse
    {
        $name = $request->input('name');
        if (! $name) {
            return ApiResponse::error('Group name is required', 'INVALID_INPUT', 400);
        }

        $group = BoardGroup::query()->create([
            'board_id' => $id,
            'name' => $name,
            'sort_order' => $request->input('sort_order', 0),
        ]);

        return ApiResponse::success([
            'id' => (int) $group->id,
            'board_id' => $id,
            'name' => $name,
        ], null, 201);
    }

    public function storeColumn(Request $request, int $id): JsonResponse
    {
        $name = $request->input('name');
        $keyName = $request->input('key_name');
        $columnType = $request->input('column_type');

        if (! $name || ! $keyName || ! $columnType) {
            return ApiResponse::error('Name, key_name, and column_type are required', 'INVALID_INPUT', 400);
        }

        $col = BoardColumn::query()->create([
            'board_id' => $id,
            'name' => $name,
            'key_name' => $keyName,
            'column_type' => $columnType,
            'settings_json' => $request->input('settings_json'),
            'is_required' => (bool) $request->input('is_required', false),
        ]);

        return ApiResponse::success([
            'id' => (int) $col->id,
            'board_id' => $id,
            'name' => $name,
            'key_name' => $keyName,
            'column_type' => $columnType,
        ], null, 201);
    }

    public function storeItem(Request $request, int $id): JsonResponse
    {
        $title = $request->input('title');
        if (! $title) {
            return ApiResponse::error('Item title is required', 'INVALID_INPUT', 400);
        }

        /** @var User|null $user */
        $user = $request->attributes->get('auth_user') ?? $request->user();
        $status = $request->input('status') ?: 'New';

        $item = BoardItem::query()->create([
            'board_id' => $id,
            'group_id' => $request->input('group_id'),
            'title' => $title,
            'entity_type' => $request->input('entity_type'),
            'entity_id' => $request->input('entity_id'),
            'owner_user_id' => $request->input('owner_user_id') ?: $user?->id,
            'status' => $status,
            'due_date' => $request->input('due_date'),
        ]);

        return ApiResponse::success([
            'id' => (int) $item->id,
            'board_id' => $id,
            'title' => $title,
            'status' => $status,
        ], null, 201);
    }

    public function updateItemValues(Request $request, int $id): JsonResponse
    {
        $columnId = $request->input('column_id');
        if (! $columnId) {
            return ApiResponse::error('column_id is required', 'INVALID_INPUT', 400);
        }

        $valueJson = $request->input('value_json');
        if (is_array($valueJson)) {
            $valueJson = json_encode($valueJson);
        }

        BoardItemValue::query()->updateOrCreate(
            [
                'board_item_id' => $id,
                'column_id' => $columnId,
            ],
            [
                'value_text' => $request->input('value_text'),
                'value_json' => is_string($valueJson) ? json_decode($valueJson, true) : $valueJson,
            ],
        );

        return ApiResponse::success([
            'board_item_id' => $id,
            'column_id' => (int) $columnId,
            'updated' => true,
        ]);
    }
}
