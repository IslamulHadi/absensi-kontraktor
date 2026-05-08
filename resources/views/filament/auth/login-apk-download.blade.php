@php
    $apkV8aPath = public_path('apk/absen-v8a.apk');
    $apkV7aPath = public_path('apk/absen-v7a.apk');
@endphp

@if (file_exists($apkV8aPath) || file_exists($apkV7aPath))
    <div class="mt-4 flex flex-wrap justify-center gap-2">
        @if (file_exists($apkV8aPath))
            <x-filament::button
                color="gray"
                href="{{ asset('apk/absen-v8a.apk') }}"
                icon="heroicon-o-arrow-down-tray"
                outlined
                tag="a"
            >
                Install Android APK (v8a)
            </x-filament::button>
        @endif

        @if (file_exists($apkV7aPath))
            <x-filament::button
                color="gray"
                href="{{ asset('apk/absen-v7a.apk') }}"
                icon="heroicon-o-arrow-down-tray"
                outlined
                tag="a"
            >
                Install Android APK (v7a)
            </x-filament::button>
        @endif
    </div>
@endif
