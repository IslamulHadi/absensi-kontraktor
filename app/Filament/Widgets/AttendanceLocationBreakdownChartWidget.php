<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use App\Models\AttendanceLocation;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class AttendanceLocationBreakdownChartWidget extends ChartWidget
{
    protected static ?int $sort = -2;

    protected ?string $pollingInterval = '30s';

    protected ?string $heading = 'Distribusi per lokasi';

    protected ?string $description = 'Jumlah catatan absensi pada setiap lokasi aktif untuk periode terpilih.';

    protected ?string $maxHeight = '280px';

    /**
     * @var int | string | array<string, int | null>
     */
    protected int|string|array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];

    public ?string $filter = 'today';

    /**
     * @return array<string, string>
     */
    protected function getFilters(): ?array
    {
        return [
            'today' => 'Hari ini',
            '7' => '7 hari terakhir',
            '30' => '30 hari terakhir',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $today = Carbon::today();
        $filter = $this->filter ?? 'today';

        $query = Attendance::query();

        if ($filter === 'today') {
            $query->whereDate('work_date', $today);
        } else {
            $days = (int) $filter;
            $query->whereBetween('work_date', [
                $today->copy()->subDays(max($days - 1, 0))->toDateString(),
                $today->toDateString(),
            ]);
        }

        $locations = AttendanceLocation::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        if ($locations->isEmpty()) {
            return [
                'datasets' => [
                    [
                        'label' => 'Catatan',
                        'data' => [],
                        'backgroundColor' => [],
                    ],
                ],
                'labels' => [],
            ];
        }

        $counts = (clone $query)
            ->whereIn('attendance_location_id', $locations->pluck('id'))
            ->selectRaw('attendance_location_id, COUNT(*) as total')
            ->groupBy('attendance_location_id')
            ->pluck('total', 'attendance_location_id');

        $labels = [];
        $data = [];

        foreach ($locations as $location) {
            $labels[] = $location->name;
            $data[] = (int) ($counts[$location->id] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Catatan',
                    'data' => $data,
                    'backgroundColor' => 'rgba(14, 165, 233, 0.65)',
                    'borderColor' => 'rgb(14, 165, 233)',
                    'borderWidth' => 1,
                    'borderRadius' => 6,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
        ];
    }
}
