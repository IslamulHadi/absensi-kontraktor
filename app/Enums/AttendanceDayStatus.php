<?php

namespace App\Enums;

enum AttendanceDayStatus: string
{
    case Incomplete = 'incomplete';
    case Present = 'present';
    case Absent = 'absent';
    case OnLeave = 'on_leave';

    public function label(): string
    {
        return match ($this) {
            self::Incomplete => 'Belum lengkap',
            self::Present => 'Hadir lengkap',
            self::Absent => 'Tidak hadir',
            self::OnLeave => 'Izin / cuti',
        };
    }
}
