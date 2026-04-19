<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ClockInAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->employee !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'attendance_location_id' => ['required', 'integer', 'exists:attendance_locations,id'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'photo' => ['required', 'image', 'max:5120'],
            'client_request_id' => ['nullable', 'string', 'max:64'],
            'client_recorded_at' => ['nullable', 'string', 'max:48'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $user = $this->user();
            if ($user === null || $user->employee === null) {
                return;
            }

            $locationId = (int) $this->input('attendance_location_id');
            [$availableLocations] = $user->employee->resolveMobileAttendanceLocations();

            if ($availableLocations === []) {
                $validator->errors()->add(
                    'attendance_location_id',
                    'Tidak ada lokasi absensi yang tersedia. Hubungi admin untuk menetapkan lokasi atau mengatur lokasi default.'
                );

                return;
            }

            $allowedLocationIds = collect($availableLocations)->pluck('id')->all();
            if (! in_array($locationId, $allowedLocationIds, true)) {
                $validator->errors()->add(
                    'attendance_location_id',
                    'Lokasi absensi tidak valid. Pilih salah satu lokasi absensi yang ditetapkan untuk akun Anda.'
                );
            }
        });
    }
}
