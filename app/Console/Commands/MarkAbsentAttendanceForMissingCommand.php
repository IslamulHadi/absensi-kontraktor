<?php

namespace App\Console\Commands;

use App\Actions\MarkAbsentAttendanceForMissingEmployees;
use Carbon\Carbon;
use Illuminate\Console\Command;

class MarkAbsentAttendanceForMissingCommand extends Command
{
    protected $signature = 'attendance:mark-absent-for-missing {--date= : Work date (Y-m-d); default is today in attendance day-close timezone}';

    protected $description = 'Record absent status for active employees who have no attendance row for the work date (same calendar day for all staff, using the configured day-close timezone).';

    public function handle(MarkAbsentAttendanceForMissingEmployees $markAbsent): int
    {
        $timezone = (string) config('attendance.day_close_timezone');

        $dateOption = $this->option('date');
        if (is_string($dateOption) && $dateOption !== '') {
            $parsed = Carbon::createFromFormat('Y-m-d', $dateOption);
            if ($parsed === false || $parsed->format('Y-m-d') !== $dateOption) {
                $this->components->error('Invalid --date; use Y-m-d.');

                return self::FAILURE;
            }
            $workDate = $parsed->toDateString();
        } else {
            $workDate = Carbon::now($timezone)->toDateString();
        }

        $this->components->info(sprintf(
            'Closing work date %s using day-close timezone %s.',
            $workDate,
            $timezone,
        ));

        $created = $markAbsent->handle($workDate);

        $this->components->info(sprintf('Created %d absent attendance row(s).', $created));

        return self::SUCCESS;
    }
}
