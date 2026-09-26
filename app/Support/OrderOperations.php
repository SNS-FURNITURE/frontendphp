<?php

namespace App\Support;

final class OrderOperations
{
    public const ROLE_OMS = 'operations_manager_showroom';

    public const ROLE_OMF = 'operations_manager_factory';

    public const ROLE_ASSEMBLER = 'assembler';

    public const ROLE_PROCUREMENT = 'procurement';

    public const ROLE_DESIGNER = 'designer';

    public const ROLE_PRODUCT_MANAGER = 'product_manager';

    public const ROLE_COMPANY_MANAGER = 'company_manager';

    public const INTAKE_PENDING = 'pending';

    public const INTAKE_UNDER_REVIEW = 'under_review';

    public const INTAKE_AWAITING_CM = 'awaiting_cm';

    public const INTAKE_ACCEPTED = 'accepted';

    public const INTAKE_REJECTED = 'rejected';

    public const INTAKE_RESUBMITTED = 'resubmitted';

    public const PHASE_DESIGN = 'design';

    public const PHASE_FACTORY_COLORING = 'factory_coloring';

    public const PHASE_MATERIALS = 'materials';

    public const PHASE_ASSEMBLY = 'assembly';

    public const PHASE_DELIVERY = 'delivery';

    public const PHASE_PENDING = 'pending';

    public const PHASE_IN_PROGRESS = 'in_progress';

    public const PHASE_COMPLETED = 'completed';

    public const CHECKPOINT_3D = '3d_completed';

    public const CHECKPOINT_2D = '2d_converted';

    public const CHECKPOINT_MEASUREMENT = 'measurement_done';

    public const STOCK_PENDING = 'pending';

    public const STOCK_AVAILABLE = 'available';

    public const STOCK_UNAVAILABLE = 'unavailable';

    public const STOCK_RELEASE_REQUESTED = 'release_requested';

    public const STOCK_RELEASED = 'released';

    public const MESSAGE_OPS = 'ops';

    public const MESSAGE_PRODUCTION_UPDATE = 'production_update';

    public const PROCUREMENT_OPEN = 'open';

    public const PROCUREMENT_QUOTED = 'quoted';

    public const PROCUREMENT_PENDING_APPROVAL = 'pending_approval';

    public const PROCUREMENT_APPROVED = 'approved';

    public const PROCUREMENT_REJECTED = 'rejected';

    public const PROCUREMENT_FINALIZED = 'finalized';

    /**
     * Intakes still on the OMS review queue (not yet with CM or in production).
     *
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
     * Intakes that already left the OMS queue (CM or production).
     *
     * @return list<string>
     */
    public static function intakePostReviewStatuses(): array
    {
        return [
            self::INTAKE_AWAITING_CM,
            self::INTAKE_ACCEPTED,
        ];
    }

    /**
     * Intakes visible on the OMS live pipeline board.
     *
     * @return list<string>
     */
    public static function intakePipelineStatuses(): array
    {
        return [
            self::INTAKE_ACCEPTED,
        ];
    }

    /**
     * @return list<string>
     */
    public static function phaseKeys(): array
    {
        return [
            self::PHASE_DESIGN,
            self::PHASE_FACTORY_COLORING,
            self::PHASE_MATERIALS,
            self::PHASE_ASSEMBLY,
            self::PHASE_DELIVERY,
        ];
    }

    /**
     * Default seeded phase labels for a new accepted intake.
     *
     * @return array<string, string>
     */
    public static function defaultPhaseLabels(): array
    {
        return [
            self::PHASE_DESIGN => 'Designing',
            self::PHASE_FACTORY_COLORING => 'Factory coloring',
            self::PHASE_MATERIALS => 'Materials',
            self::PHASE_ASSEMBLY => 'Assembly',
            self::PHASE_DELIVERY => 'Delivery',
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
