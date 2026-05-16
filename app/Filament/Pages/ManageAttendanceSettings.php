<?php

namespace App\Filament\Pages;

use App\Models\AttendanceSetting;
use App\Models\User;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Halaman pengaturan singleton untuk toleransi absen masuk. Akses dibatasi
 * untuk Admin dan SuperAdmin — sesuai dengan kebijakan `canAccessPanel`.
 *
 * @property-read Schema $form
 */
class ManageAttendanceSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?string $navigationLabel = 'Toleransi Absen Masuk';

    protected static ?string $title = 'Toleransi Absen Masuk';

    protected static string|\UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?int $navigationSort = 90;

    protected static ?string $slug = 'toleransi-absen-masuk';

    protected string $view = 'filament.pages.manage-attendance-settings';

    /**
     * @var array<string, mixed>
     */
    public array $data = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && ($user->isAdmin() || $user->isSuperAdmin());
    }

    public function mount(): void
    {
        $setting = AttendanceSetting::current();

        $this->form->fill([
            'clock_in_on_time_at' => $setting->clock_in_on_time_at,
            'clock_in_tolerance_minutes' => $setting->clock_in_tolerance_minutes,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Toleransi keterlambatan absen masuk')
                    ->description('Acuan jam masuk standar perusahaan dan toleransi keterlambatan. '
                        .'Nilai akan di-snapshot ke setiap kehadiran baru saat pegawai absen masuk.')
                    ->schema([
                        TimePicker::make('clock_in_on_time_at')
                            ->label('Jam masuk standar')
                            ->seconds(false)
                            ->required()
                            ->helperText('Mis. 08:00. Pegawai yang absen sebelum jam ini + toleransi dianggap tepat waktu.'),
                        TextInput::make('clock_in_tolerance_minutes')
                            ->label('Toleransi (menit)')
                            ->integer()
                            ->required()
                            ->minValue(0)
                            ->maxValue(240)
                            ->suffix('menit')
                            ->helperText('Jumlah menit grace period setelah jam masuk standar sebelum dianggap telat.'),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $setting = AttendanceSetting::current();
        $setting->fill([
            'clock_in_on_time_at' => $data['clock_in_on_time_at'] ?? null,
            'clock_in_tolerance_minutes' => (int) ($data['clock_in_tolerance_minutes'] ?? 0),
        ]);
        $setting->save();

        $this->form->fill([
            'clock_in_on_time_at' => $setting->clock_in_on_time_at,
            'clock_in_tolerance_minutes' => $setting->clock_in_tolerance_minutes,
        ]);

        Notification::make()
            ->title('Pengaturan tersimpan')
            ->body('Toleransi absen masuk berhasil diperbarui.')
            ->success()
            ->send();
    }
}
