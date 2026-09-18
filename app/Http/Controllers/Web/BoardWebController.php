<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Board;
use App\Models\BoardColumn;
use App\Models\BoardGroup;
use App\Models\BoardItem;
use App\Models\BoardItemValue;
use App\Models\Workspace;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BoardWebController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(): View
    {
        abort_unless(auth()->user()?->canViewBoards(), 403);

        return view('boards.index', [
            'workspaces' => Workspace::query()->orderByDesc('created_at')->get(),
            'boards' => Board::query()->where('is_active', true)->orderByDesc('created_at')->get(),
            'canCreate' => auth()->user()->canCreateBoards(),
        ]);
    }

    public function storeWorkspace(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->canCreateBoards(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2'],
        ]);

        Workspace::query()->create([
            'name' => $validated['name'],
            'visibility' => 'PRIVATE',
        ]);

        return back()->with('status', 'Workspace created');
    }

    public function storeBoard(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->canCreateBoards(), 403);

        $validated = $request->validate([
            'workspace_id' => ['required', 'integer', 'min:1'],
            'name' => ['required', 'string', 'min:2'],
            'board_type' => ['nullable', 'in:table,kanban,timeline,custom,main'],
            'module_key' => ['nullable', 'string'],
        ]);

        $board = DB::transaction(function () use ($validated) {
            $board = Board::query()->create([
                'workspace_id' => $validated['workspace_id'],
                'name' => $validated['name'],
                'board_type' => $validated['board_type'] ?? 'table',
                'module_key' => $validated['module_key'] ?? null,
                'is_active' => true,
                'created_by_user_id' => auth()->id(),
            ]);
            BoardGroup::query()->create([
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

            return $board;
        });

        $this->audit->log(auth()->user(), 'board', (int) $board->id, 'CREATE_BOARD', [
            'name' => $validated['name'],
            'workspace_id' => $validated['workspace_id'],
        ], $request);

        return redirect()->route('boards.show', $board)->with('status', 'Board created');
    }

    public function show(Board $board): View
    {
        abort_unless(auth()->user()?->canViewBoards(), 403);

        $board->load(['groups', 'columns', 'items']);

        $values = BoardItemValue::query()
            ->whereIn('board_item_id', $board->items->pluck('id'))
            ->get()
            ->groupBy('board_item_id');

        return view('boards.show', [
            'board' => $board,
            'values' => $values,
            'canEdit' => auth()->user()->canCreateBoards() || auth()->user()->hasPermission('boards', 'edit'),
        ]);
    }

    public function storeItem(Request $request, Board $board): RedirectResponse
    {
        abort_unless(auth()->user()?->canViewBoards(), 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'min:1'],
            'group_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'string'],
        ]);

        BoardItem::query()->create([
            'board_id' => $board->id,
            'group_id' => $validated['group_id'] ?? $board->groups->first()?->id,
            'title' => $validated['title'],
            'owner_user_id' => auth()->id(),
            'status' => $validated['status'] ?? 'New',
        ]);

        return back()->with('status', 'Item added');
    }

    public function updateItemValue(Request $request, BoardItem $item): RedirectResponse
    {
        abort_unless(auth()->user()?->canViewBoards(), 403);

        $validated = $request->validate([
            'column_id' => ['required', 'integer'],
            'value_text' => ['nullable', 'string'],
        ]);

        BoardItemValue::query()->updateOrCreate(
            [
                'board_item_id' => $item->id,
                'column_id' => $validated['column_id'],
            ],
            ['value_text' => $validated['value_text'] ?? null],
        );

        if ($col = BoardColumn::query()->find($validated['column_id'])) {
            if ($col->key_name === 'status' && isset($validated['value_text'])) {
                $item->status = $validated['value_text'];
                $item->save();
            }
        }

        return back()->with('status', 'Cell updated');
    }
}
