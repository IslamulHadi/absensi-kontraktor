<?php

namespace App\Actions;

use App\Enums\AttendanceDayStatus;
use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Database\Query\Builder;

final class MarkAbsentAttendanceForMissingEmployees
{
    /**
     * Insert one absent attendance per active employee who has no row for the work date.
     *
     * @return int Number of rows created
     */
    public function handle(string $workDate): int
    {
        $created = 0;

        Employee::query()
            ->where('is_active', true)
            ->whereNotExists(function (Builder $query) use ($workDate): void {
                $query->selectRaw('1')
                    ->from('attendances')
                    ->whereColumn('attendances.employee_id', 'employees.id')
                    ->where('attendances.work_date', '=', $workDate);
            })
            ->orderBy('id')
            ->chunkById(100, function ($employees) use ($workDate, &$created): void {
                foreach ($employees as $employee) {
                    $attendance = Attendance::query()->firstOrCreate(
                        [
                            'employee_id' => $employee->id,
                            'work_date' => $workDate,
                        ],
                        [
                            'status' => AttendanceDayStatus::Absent,
                        ],
                    );

                    if ($attendance->wasRecentlyCreated) {
                        $created++;
                    }
                }
            });

        return $created;
    }
}
