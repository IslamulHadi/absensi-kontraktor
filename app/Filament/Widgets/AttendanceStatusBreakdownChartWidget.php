<?php

namespace App\Filament\Widgets;

use App\Enums\AttendanceDayStatus;
use App\Models\Attendance;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class AttendanceStatusBreakdownChartWidget extends ChartWidget
{
    protected static ?int $sort = -3;

    protected ?string $pollingInterval = '30s';

    protected ?string $heading = 'Komposisi status hari ini';

    protected ?string $description = 'Distribusi catatan absensi berdasarkan status untuk tanggal hari ini.';

    protected ?string $maxHeight = '280px';

    /**
     * @var int | string | array<string, int | null>
     */
    protected int|string|array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $today = Carbon::today();

        $rows = Attendance::query()
            ->whereDate('work_date', $today)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $present = (int) ($rows[AttendanceDayStatus::Present->value] ?? 0);
        $incomplete = (int) ($rows[AttendanceDayStatus::Incomplete->value] ?? 0);
        $absent = (int) ($rows[AttendanceDayStatus::Absent->value] ?? 0);
        $onLeave = (int) ($rows[AttendanceDayStatus::OnLeave->value] ?? 0);

        return [
            'datasets' => [
                [
                    'label' => 'Catatan hari ini',
                    'data' => [$present, $incomplete, $absent, $onLeave],
                    'backgroundColor' => [
                        'rgb(34, 197, 94)',
                        'rgb(234, 179, 8)',
                        'rgb(239, 68, 68)',
                        'rgb(148, 163, 184)',
                    ],
                    'borderWidth' => 0,
                ],
            ],
            'labels' => [
                AttendanceDayStatus::Present->label(),
                AttendanceDayStatus::Incomplete->label(),
                AttendanceDayStatus::Absent->label(),
                AttendanceDayStatus::OnLeave->label(),
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
            'cutout' => '62%',
        ];
    }
}
