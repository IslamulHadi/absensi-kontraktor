<?php

use App\Models\Employee;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_location_employee', function (Blueprint $table) {
            $table->boolean('is_primary')
                ->default(false)
                ->after('attendance_location_id');
        });

        Employee::query()->orderBy('id')->chunkById(100, function ($employees): void {
            foreach ($employees as $employee) {
                assert($employee instanceof Employee);
                $first = $employee->attendanceLocations()
                    ->orderBy('attendance_locations.name')
                    ->first();
                if ($first !== null) {
                    $employee->attendanceLocations()->updateExistingPivot($first->id, [
                        'is_primary' => true,
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendance_location_employee', function (Blueprint $table) {
            $table->dropColumn('is_primary');
        });
    }
};
