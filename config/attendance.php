<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Day close timezone
    |--------------------------------------------------------------------------
    |
    | Single timezone used for the calendar work_date when the scheduler runs
    | the daily "missing attendance → absent" job. All employees share this
    | cutover so "hari kerja" is unambiguous company-wide. Defaults match
    | config/app.php timezone unless ATTENDANCE_DAY_CLOSE_TIMEZONE is set.
    |
    */

    'day_close_timezone' => env('ATTENDANCE_DAY_CLOSE_TIMEZONE', 'Asia/Makassar'),

    /*
    |--------------------------------------------------------------------------
    | Day close time
    |--------------------------------------------------------------------------
    |
    | Local wall-clock time in day_close_timezone when the job is scheduled.
    | Format: H:i (e.g. 23:59). Override with ATTENDANCE_DAY_CLOSE_TIME.
    |
    */

    'day_close_time' => env('ATTENDANCE_DAY_CLOSE_TIME', '23:59'),

];
