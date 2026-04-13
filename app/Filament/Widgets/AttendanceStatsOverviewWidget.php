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

    protected ?string $heading = 'Ringkasan absensi';

    protected ?string $description = 'Angka untuk tanggal hari ini (zona waktu aplikasi). Grafik menampilkan tren 7 hari terakhir.';

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $today = Carbon::today();

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

        $onLeaveToday = (clone $todayQuery)
            ->where('status', AttendanceDayStatus::OnLeave)
            ->count();

        $activeEmployees = Employee::query()->where('is_active', true)->count();

        $activeLocations = AttendanceLocation::query()->where('is_active', true)->count();

        $distinctEmployeesToday = $activeEmployees > 0
            ? (int) Attendance::query()
                ->whereDate('work_date', $today)
                ->distinct()
                ->count('employee_id')
            : 0;

        $chartData = collect(range(6, 0))
            ->map(fn (int $daysAgo): int => Attendance::query()
                ->whereDate('work_date', $today->copy()->subDays($daysAgo))
                ->count())
            ->all();

        return [
            Stat::make('Catatan hari ini', $totalToday)
                ->description('Total baris absensi untuk tanggal ini')
                ->descriptionIcon(Heroicon::OutlinedCalendarDays)
                ->chart($chartData)
                ->chartColor('primary')
                ->color('primary'),
            Stat::make('Hadir lengkap', $presentToday)
                ->description('Selesai masuk dan keluar')
                ->descriptionIcon(Heroicon::OutlinedCheckCircle)
                ->color('success'),
            Stat::make('Belum lengkap', $incompleteToday)
                ->description('Masih perlu dilengkapi')
                ->descriptionIcon(Heroicon::OutlinedClock)
                ->color('warning'),
            Stat::make('Tidak hadir', $absentToday)
                ->description('Dicatat absen hari ini')
                ->descriptionIcon(Heroicon::OutlinedXCircle)
                ->color('danger'),
            // Stat::make('Izin / cuti', $onLeaveToday)
            //     ->description('Status izin untuk hari ini')
            //     ->descriptionIcon(Heroicon::OutlinedPaperAirplane)
            //     ->color('gray'),
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
}
