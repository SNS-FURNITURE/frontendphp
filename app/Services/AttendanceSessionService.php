<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use DateTimeImmutable;

class AttendanceSessionService
{
    public function isSaturdayAfternoon(string $date, string $session): bool
    {
        if ($session !== 'afternoon') {
            return false;
        }

        return (int) (new DateTimeImmutable($date))->format('w') === 6;
    }

    /**
     * Upsert Saturday afternoon = present for all active employees in [from, to].
     */
    public function ensureSaturdayAfternoonsPresent(string $from, string $to): void
    {
        $employeeIds = Employee::query()->active()->pluck('id');
        if ($employeeIds->isEmpty()) {
            return;
        }

        $start = new DateTimeImmutable($from);
        $end = new DateTimeImmutable($to);

        for ($day = $start; $day <= $end; $day = $day->modify('+1 day')) {
            if ((int) $day->format('w') !== 6) {
                continue;
            }

            $date = $day->format('Y-m-d');

            foreach ($employeeIds as $employeeId) {
                $existing = Attendance::query()
                    ->where('employee_id', $employeeId)
                    ->whereDate('date', $date)
                    ->where('session', 'afternoon')
                    ->first();

                if ($existing) {
                    if ($existing->status !== 'present') {
                        $existing->forceFill(['status' => 'present'])->save();
                    }

                    continue;
                }

                Attendance::query()->create([
                    'employee_id' => $employeeId,
                    'date' => $date,
                    'session' => 'afternoon',
                    'status' => 'present',
                ]);
            }
        }
    }
}
