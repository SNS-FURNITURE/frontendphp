<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Party;
use App\Models\User;
use App\Services\AuditService;
use App\Services\CustomerIdentityService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PartyController extends Controller
{
    public function __construct(
        private AuditService $audit,
        private CustomerIdentityService $customerIdentity,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Party::query();

        if ($request->filled('type')) {
            $query->where('party_type', $request->query('type'));
        }

        if ($request->filled('approval_status')) {
            $query->where('approval_status', $request->query('approval_status'));
        }

        $rows = $query->latestFirst()->get();

        return ApiResponse::success($rows->map(fn (Party $p) => $this->serialize($p))->values()->all());
    }

    public function store(Request $request): JsonResponse
    {
        $name = $request->input('name');
        if (! $name) {
            return ApiResponse::error('Party name is required', 'INVALID_INPUT', 400);
        }

        /** @var User|null $user */
        $user = $request->attributes->get('auth_user') ?? $request->user();

        $partyType = $request->input('party_type', 'customer');
        $address = $request->input('address');

        if ($partyType === 'customer' && $this->customerIdentity->customerExists($name, is_string($address) ? $address : null)) {
            return ApiResponse::error(
                'A customer with this name and address already exists.',
                'DUPLICATE_CUSTOMER',
                409,
            );
        }

        try {
            $party = $partyType === 'customer'
                ? $this->customerIdentity->createCustomer([
                    'name' => $name,
                    'company_name' => $request->input('company_name'),
                    'phone' => $request->input('phone'),
                    'email' => $request->input('email'),
                    'address' => $address,
                    'notes' => $request->input('notes'),
                    'approval_status' => 'pending',
                    'created_by' => $user?->id,
                ])
                : Party::query()->create([
                    'party_type' => $partyType,
                    'name' => $name,
                    'company_name' => $request->input('company_name'),
                    'phone' => $request->input('phone'),
                    'email' => $request->input('email'),
                    'address' => $address,
                    'notes' => $request->input('notes'),
                    'approval_status' => 'pending',
                    'created_by' => $user?->id,
                ]);
        } catch (\InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), 'DUPLICATE_CUSTOMER', 409);
        }

        $this->audit->log($user, 'party', (int) $party->id, 'CREATE_PARTY', [
            'name' => $party->name,
            'party_type' => $party->party_type,
        ], $request);

        return ApiResponse::success($this->serialize($party->fresh()), null, 201);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->attributes->get('auth_user') ?? $request->user();

        if (! $user || ! $user->canApproveParty()) {
            return ApiResponse::error('Insufficient permissions for this action', 'FORBIDDEN', 403);
        }

        $approvalStatus = $request->input('approval_status');
        if (! in_array($approvalStatus, ['approved', 'rejected'], true)) {
            return ApiResponse::error(
                'Invalid status. Must be approved or rejected.',
                'INVALID_INPUT',
                400,
            );
        }

        $party = Party::query()->find($id);
        if (! $party) {
            return ApiResponse::error('Party not found', 'NOT_FOUND', 404);
        }

        $previous = $party->approval_status;
        $party->approval_status = $approvalStatus;
        $party->approved_by = $user->id;
        $party->save();

        $this->audit->log($user, 'party', (int) $party->id, 'APPROVAL_'.strtoupper($approvalStatus), [
            'previous_status' => $previous,
            'new_status' => $approvalStatus,
        ], $request);

        return ApiResponse::success($this->serialize($party->fresh()));
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(Party $party): array
    {
        return [
            'id' => (int) $party->id,
            'party_type' => $party->party_type,
            'name' => $party->name,
            'company_name' => $party->company_name,
            'phone' => $party->phone,
            'email' => $party->email,
            'address' => $party->address,
            'notes' => $party->notes,
            'approval_status' => $party->approval_status,
            'created_by' => $party->created_by,
            'approved_by' => $party->approved_by,
            'created_at' => $party->created_at,
            'updated_at' => $party->updated_at,
        ];
    }
}
