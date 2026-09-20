<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Machinery;
use App\Models\User;
use App\Services\AuditService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MachineryController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(Request $request): JsonResponse
    {
        $query = Machinery::query()->orderBy('name');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($workshop = $request->query('workshop')) {
            $query->where('workshop_location', $workshop);
        }

        return ApiResponse::success(
            $query->get()->map(fn (Machinery $m) => $m->toApiArray())->values()->all()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $machineCode = $request->input('machine_code');
        $name = $request->input('name');

        if (! $machineCode || ! $name) {
            return ApiResponse::error('Machine code and name are required', 'INVALID_INPUT', 400);
        }

        $machine = Machinery::query()->create([
            'machine_code' => $machineCode,
            'name' => $name,
            'category' => $request->input('category') ?: 'woodwork',
            'workshop_location' => $request->input('workshop_location') ?: 'Wood Work Shop',
            'existing_qty' => $request->input('existing_qty') ?: 1,
            'status' => 'operational',
        ]);

        /** @var User|null $user */
        $user = $request->attributes->get('auth_user') ?? $request->user();
        $this->audit->log($user, 'machinery', (int) $machine->id, 'CREATE_MACHINERY', [
            'machine_code' => $machineCode,
            'name' => $name,
        ], $request);

        return ApiResponse::success([
            'id' => (int) $machine->id,
            'machine_code' => $machine->machine_code,
            'name' => $machine->name,
            'status' => 'operational',
        ], null, 201);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $status = $request->input('status');
        $workshop = $request->input('workshop_location');

        if ($status && ! in_array($status, Machinery::STATUSES, true)) {
            return ApiResponse::error(
                'Invalid status. Must be operational, under_maintenance, or idle',
                'INVALID_INPUT',
                400
            );
        }

        $machine = Machinery::query()->find($id);
        if (! $machine) {
            return ApiResponse::error('Machinery not found', 'NOT_FOUND', 404);
        }

        if ($status) {
            $machine->status = $status;
        }
        if ($workshop !== null) {
            $machine->workshop_location = $workshop;
        }
        $machine->save();

        /** @var User|null $user */
        $user = $request->attributes->get('auth_user') ?? $request->user();
        $this->audit->log($user, 'machinery', $id, 'UPDATE_MACHINERY_STATUS', [
            'status' => $status,
            'workshop_location' => $workshop,
        ], $request);

        return ApiResponse::success([
            'id' => $id,
            'status' => $status ?? $machine->status,
            'workshop_location' => $workshop ?? $machine->workshop_location,
        ]);
    }
}
