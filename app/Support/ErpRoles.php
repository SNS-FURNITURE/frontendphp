<?php

namespace App\Support;

use App\Models\Role;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Canonical ERP role catalog — single source for seeding and admin assignment UI.
 */
class ErpRoles
{
    /**
     * @return array<string, list<string>>
     */
    public static function groups(): array
    {
        return [
            'Executive Management' => [
                'company_manager',
            ],
            'Administration & Human Resource' => [
                'hr',
            ],
            'Finance' => [
                'finance',
            ],
            'Commercial Division' => [
                'marketing_manager',
                'sales_supervisor',
                'sales',
                'operations_manager_showroom',
                'procurement',
                'inventory',
            ],
            'Production Division' => [
                'product_manager',
                'designer',
                'operations_manager_factory',
                'assembler',
            ],
        ];
    }

    /**
     * Retired role keys removed from the ERP catalog.
     *
     * @return list<string>
     */
    public static function removedRoleNames(): array
    {
        return [
            'manager',
            'supervisor',
            'project_manager',
            'lead_gen',
            'showroom',
            'advisor',
            'sales_lead_gen',
            'pm',
            'production',
            'operations_customer',
            'operations_factory',
            'procurement_operations',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function roleMigrationMap(): array
    {
        return [
            'manager' => 'company_manager',
            'supervisor' => 'sales_supervisor',
            'project_manager' => 'product_manager',
            'lead_gen' => 'sales_supervisor',
            'showroom' => 'sales_supervisor',
            'advisor' => 'sales_supervisor',
            'sales_lead_gen' => 'sales_supervisor',
            'pm' => 'product_manager',
            'production' => 'product_manager',
            'operations_customer' => 'operations_manager_showroom',
            'operations_factory' => 'operations_manager_factory',
            'procurement_operations' => 'procurement',
        ];
    }

    public static function purgeRemovedRoles(): void
    {
        foreach (self::roleMigrationMap() as $from => $to) {
            $fromRole = Role::query()->where('name', $from)->first();
            if (! $fromRole) {
                continue;
            }

            $toRole = Role::query()->where('name', $to)->first();

            if ($toRole) {
                $assignments = DB::table('user_roles')
                    ->where('role_id', $fromRole->id)
                    ->get(['user_id']);

                foreach ($assignments as $assignment) {
                    $alreadyHasTarget = DB::table('user_roles')
                        ->where('user_id', $assignment->user_id)
                        ->where('role_id', $toRole->id)
                        ->exists();

                    if ($alreadyHasTarget) {
                        DB::table('user_roles')
                            ->where('user_id', $assignment->user_id)
                            ->where('role_id', $fromRole->id)
                            ->delete();
                    } else {
                        DB::table('user_roles')
                            ->where('user_id', $assignment->user_id)
                            ->where('role_id', $fromRole->id)
                            ->update([
                                'role_id' => $toRole->id,
                            ]);
                    }
                }
            } else {
                DB::table('user_roles')->where('role_id', $fromRole->id)->delete();
            }

            DB::table('role_permissions')->where('role_id', $fromRole->id)->delete();
            Role::query()->whereKey($fromRole->id)->delete();
        }
    }

    /**
     * @return list<string>
     */
    public static function catalogNames(): array
    {
        $names = [];

        foreach (self::groups() as $groupRoles) {
            foreach ($groupRoles as $name) {
                $names[] = $name;
            }
        }

        return array_values(array_unique($names));
    }

    /**
     * Role names allowed on the admin create/update forms (catalog + other non-admin DB roles).
     *
     * @return list<string>
     */
    public static function assignableNames(): array
    {
        self::syncCatalog();

        return Role::query()
            ->whereRaw('LOWER(name) != ?', ['admin'])
            ->orderBy('name')
            ->pluck('name')
            ->all();
    }

    public static function syncCatalog(): void
    {
        foreach (self::catalogNames() as $name) {
            Role::query()->firstOrCreate(
                ['name' => $name],
                [
                    'description' => strtoupper(str_replace('_', ' ', $name)).' organizational role scope',
                    'created_at' => now(),
                ]
            );
        }
    }

    /**
     * Roles assignable from the admin users screen (all ERP roles except admin).
     *
     * @return array<string, Collection<int, Role>>
     */
    public static function assignableGrouped(): array
    {
        self::syncCatalog();

        $byName = Role::query()
            ->whereIn('name', self::catalogNames())
            ->get()
            ->keyBy('name');

        $grouped = [];

        foreach (self::groups() as $label => $roleNames) {
            $grouped[$label] = collect($roleNames)
                ->map(fn (string $name) => $byName->get($name))
                ->filter()
                ->values();
        }

        $other = Role::query()
            ->whereRaw('LOWER(name) != ?', ['admin'])
            ->whereNotIn('name', self::catalogNames())
            ->orderBy('name')
            ->get();

        if ($other->isNotEmpty()) {
            $grouped['Other'] = $other;
        }

        return $grouped;
    }
}
