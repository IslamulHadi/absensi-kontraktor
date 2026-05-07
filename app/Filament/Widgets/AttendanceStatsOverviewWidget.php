<?php

namespace App\Filament\Widgets;

use App\Enums\AttendanceDayStatus;
use App\Models\Attendance;
use App\Models\AttendanceLocation;
use App\Models\Employee;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class AttendanceStatsOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = -5;

    protected ?string $pollingInterval = '30s';

    protected ?string $heading = 'Ringkasan absensi';

    protected ?string $description = 'Angka untuk tanggal hari ini (zona waktu aplikasi). Grafik menampilkan tren 7 hari terakhir.';

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $today = Carbon::today();
        $yesterday = $today->copy()->subDay();

        $todayQuery = Attendance::query()->whereDate('work_date', $today);

        $totalToday = (clone $todayQuery)->count();

        $presentToday = (clone $todayQuery)
            ->where('status', AttendanceDayStatus::Present)
            ->count();

        $incompleteToday = (clone $todayQuery)
            ->where('status', AttendanceDayStatus::Incomplete)
            ->count();

        $absentToday = (clone $todayQuery)
            ->where('status', AttendanceDayStatus::Absent)
            ->count();

        $activeEmployees = Employee::query()->where('is_active', true)->count();

        $activeLocations = AttendanceLocation::query()->where('is_active', true)->count();

        $distinctEmployeesToday = $activeEmployees > 0
            ? (int) Attendance::query()
                ->whereDate('work_date', $today)
                ->distinct()
                ->count('employee_id')
            : 0;

        $totalChartData = collect(range(6, 0))
            ->map(fn (int $daysAgo): int => Attendance::query()
                ->whereDate('work_date', $today->copy()->subDays($daysAgo))
                ->count())
            ->all();

        $presentChartData = collect(range(6, 0))
            ->map(fn (int $daysAgo): int => Attendance::query()
                ->whereDate('work_date', $today->copy()->subDays($daysAgo))
                ->where('status', AttendanceDayStatus::Present)
                ->count())
            ->all();

        $incompleteChartData = collect(range(6, 0))
            ->map(fn (int $daysAgo): int => Attendance::query()
                ->whereDate('work_date', $today->copy()->subDays($daysAgo))
                ->where('status', AttendanceDayStatus::Incomplete)
                ->count())
            ->all();

        $absentChartData = collect(range(6, 0))
            ->map(fn (int $daysAgo): int => Attendance::query()
                ->whereDate('work_date', $today->copy()->subDays($daysAgo))
                ->where('status', AttendanceDayStatus::Absent)
                ->count())
            ->all();

        $presentYesterday = Attendance::query()
            ->whereDate('work_date', $yesterday)
            ->where('status', AttendanceDayStatus::Present)
            ->count();

        $attendanceRate = $activeEmployees > 0
            ? (int) round(($presentToday / $activeEmployees) * 100)
            : 0;

        $rateDelta = $this->describeDelta($presentToday, $presentYesterday);

        return [
            Stat::make('Catatan hari ini', $totalToday)
                ->description('Total baris absensi untuk tanggal ini')
                ->descriptionIcon(Heroicon::OutlinedCalendarDays)
                ->chart($totalChartData)
                ->chartColor('primary')
                ->color('primary'),
            Stat::make('Tingkat kehadiran', $attendanceRate.'%')
                ->description($rateDelta['text'])
                ->descriptionIcon($rateDelta['icon'])
                ->color($rateDelta['color']),
            Stat::make('Hadir lengkap', $presentToday)
                ->description('Selesai masuk dan keluar')
                ->descriptionIcon(Heroicon::OutlinedCheckCircle)
                ->chart($presentChartData)
                ->chartColor('success')
                ->color('success'),
            Stat::make('Belum lengkap', $incompleteToday)
                ->description('Masih perlu dilengkapi')
                ->descriptionIcon(Heroicon::OutlinedClock)
                ->chart($incompleteChartData)
                ->chartColor('warning')
                ->color('warning'),
            Stat::make('Tidak hadir', $absentToday)
                ->description('Dicatat absen hari ini')
                ->descriptionIcon(Heroicon::OutlinedXCircle)
                ->chart($absentChartData)
                ->chartColor('danger')
                ->color('danger'),
            Stat::make('Pegawai aktif', $activeEmployees)
                ->description(
                    $activeEmployees > 0
                        ? sprintf('%d sudah punya catatan hari ini', min($distinctEmployeesToday, $activeEmployees))
                        : 'Belum ada pegawai aktif'
                )
                ->descriptionIcon(Heroicon::OutlinedUsers)
                ->color('gray'),
            Stat::make('Lokasi aktif', $activeLocations)
                ->description('Lokasi absensi yang dapat dipakai')
                ->descriptionIcon(Heroicon::OutlinedMapPin)
                ->color('gray'),
        ];
    }

    /**
     * @return array{text: string, icon: Heroicon, color: string}
     */
    private function describeDelta(int $today, int $yesterday): array
    {
        $diff = $today - $yesterday;

        if ($yesterday === 0 && $today === 0) {
            return [
                'text' => 'Belum ada data kemarin',
                'icon' => Heroicon::OutlinedMinusSmall,
                'color' => 'gray',
            ];
        }

        if ($diff === 0) {
            return [
                'text' => 'Sama dengan kemarin',
                'icon' => Heroicon::OutlinedMinusSmall,
                'color' => 'gray',
            ];
        }

        if ($diff > 0) {
            return [
                'text' => sprintf('Naik %d dari kemarin', $diff),
                'icon' => Heroicon::OutlinedArrowTrendingUp,
                'color' => 'success',
            ];
        }

        return [
            'text' => sprintf('Turun %d dari kemarin', abs($diff)),
            'icon' => Heroicon::OutlinedArrowTrendingDown,
            'color' => 'danger',
        ];
    }
}
