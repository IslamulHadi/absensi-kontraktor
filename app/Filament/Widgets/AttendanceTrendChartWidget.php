<?php

namespace App\Filament\Widgets;

use App\Enums\AttendanceDayStatus;
use App\Models\Attendance;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class AttendanceTrendChartWidget extends ChartWidget
{
    protected static ?int $sort = -4;

    protected ?string $pollingInterval = '30s';

    protected ?string $heading = 'Tren absensi';

    protected ?string $description = 'Distribusi status absensi per hari pada periode terpilih.';

    protected ?string $maxHeight = '280px';

    /**
     * @var int | string | array<string, int | null>
     */
    protected int|string|array $columnSpan = 'full';

    public ?string $filter = '14';

    /**
     * @return array<string, string>
     */
    protected function getFilters(): ?array
    {
        return [
            '7' => '7 hari terakhir',
            '14' => '14 hari terakhir',
            '30' => '30 hari terakhir',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $days = (int) ($this->filter ?? 14);
        $today = Carbon::today();

        $dates = collect(range($days - 1, 0))
            ->map(fn (int $offset): Carbon => $today->copy()->subDays($offset));

        $present = [];
        $incomplete = [];
        $absent = [];
        $labels = [];

        foreach ($dates as $date) {
            $labels[] = $date->translatedFormat('d M');

            $rows = Attendance::query()
                ->whereDate('work_date', $date)
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');

            $present[] = (int) ($rows[AttendanceDayStatus::Present->value] ?? 0);
            $incomplete[] = (int) ($rows[AttendanceDayStatus::Incomplete->value] ?? 0);
            $absent[] = (int) ($rows[AttendanceDayStatus::Absent->value] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Hadir lengkap',
                    'data' => $present,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.18)',
                    'borderColor' => 'rgb(34, 197, 94)',
                    'borderWidth' => 2,
                    'tension' => 0.35,
                    'fill' => true,
                ],
                [
                    'label' => 'Belum lengkap',
                    'data' => $incomplete,
                    'backgroundColor' => 'rgba(234, 179, 8, 0.18)',
                    'borderColor' => 'rgb(234, 179, 8)',
                    'borderWidth' => 2,
                    'tension' => 0.35,
                    'fill' => true,
                ],
                [
                    'label' => 'Tidak hadir',
                    'data' => $absent,
                    'backgroundColor' => 'rgba(239, 68, 68, 0.18)',
                    'borderColor' => 'rgb(239, 68, 68)',
                    'borderWidth' => 2,
                    'tension' => 0.35,
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
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
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
            'interaction' => [
                'mode' => 'index',
                'intersect' => false,
            ],
        ];
    }
}
