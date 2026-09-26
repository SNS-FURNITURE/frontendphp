<?php

namespace App\Support;

final class OrderOperations
{
    public const ROLE_OMS = 'operations_manager_showroom';

    public const ROLE_OMF = 'operations_manager_factory';

    public const ROLE_ASSEMBLER = 'assembler';

    public const ROLE_PROCUREMENT = 'procurement';

    public const ROLE_DESIGNER = 'designer';

    public const ROLE_COMPANY_MANAGER = 'company_manager';

    public const INTAKE_PENDING = 'pending';

    public const INTAKE_UNDER_REVIEW = 'under_review';

    public const INTAKE_ACCEPTED = 'accepted';

    public const INTAKE_REJECTED = 'rejected';

    public const INTAKE_RESUBMITTED = 'resubmitted';

    public const PHASE_DESIGN = 'design';

    public const PHASE_MATERIALS = 'materials';

    public const PHASE_ASSEMBLY = 'assembly';

    public const PHASE_DELIVERY = 'delivery';

    public const PHASE_PENDING = 'pending';

    public const PHASE_IN_PROGRESS = 'in_progress';

    public const PHASE_COMPLETED = 'completed';

    public const CHECKPOINT_3D = '3d_completed';

    public const CHECKPOINT_2D = '2d_converted';

    public const STOCK_PENDING = 'pending';

    public const STOCK_AVAILABLE = 'available';

    public const STOCK_UNAVAILABLE = 'unavailable';

    public const STOCK_RELEASED = 'released';

    public const PROCUREMENT_OPEN = 'open';

    public const PROCUREMENT_QUOTED = 'quoted';

    public const PROCUREMENT_PENDING_APPROVAL = 'pending_approval';

    public const PROCUREMENT_APPROVED = 'approved';

    public const PROCUREMENT_REJECTED = 'rejected';

    public const PROCUREMENT_FINALIZED = 'finalized';

    /**
     * @return list<string>
     */
    public static function intakeOpenStatuses(): array
    {
        return [
            self::INTAKE_PENDING,
            self::INTAKE_UNDER_REVIEW,
            self::INTAKE_RESUBMITTED,
            self::INTAKE_REJECTED,
        ];
    }

    /**
     * @return list<string>
     */
    public static function phaseKeys(): array
    {
        return [
            self::PHASE_DESIGN,
            self::PHASE_MATERIALS,
            self::PHASE_ASSEMBLY,
            self::PHASE_DELIVERY,
        ];
    }

    /**
     * Soft aliases for legacy role names still referenced in older code paths.
     *
     * @return list<string>
     */
    public static function roleAliases(string $roleName): array
    {
        $map = [
            'operations_customer' => [self::ROLE_OMS, 'operations_customer'],
            self::ROLE_OMS => [self::ROLE_OMS, 'operations_customer'],
            'operations_factory' => [self::ROLE_OMF, 'operations_factory'],
            self::ROLE_OMF => [self::ROLE_OMF, 'operations_factory'],
            'procurement_operations' => [self::ROLE_PROCUREMENT, 'procurement_operations'],
            self::ROLE_PROCUREMENT => [self::ROLE_PROCUREMENT, 'procurement_operations'],
        ];

        return $map[$roleName] ?? [$roleName];
    }
}
