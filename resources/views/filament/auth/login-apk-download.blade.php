@if (file_exists(public_path('apk/absen.apk')))
    <div class="flex justify-center">
        <x-filament::button
            class="mt-4"
            color="gray"
            href="{{ route('downloads.absen-apk') }}"
            icon="heroicon-o-arrow-down-tray"
            outlined
            tag="a"
        >
            Unduh aplikasi Android (APK)
        </x-filament::button>
    </div>
@endif
