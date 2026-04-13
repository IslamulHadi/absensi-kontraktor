<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ClockOutAttendanceRequest extends FormRequest
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
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'photo' => ['required', 'image', 'max:5120'],
            'client_request_id' => ['nullable', 'string', 'max:64'],
            'client_recorded_at' => ['nullable', 'string', 'max:48'],
            'work_date' => ['nullable', 'date_format:Y-m-d'],
        ];
    }
}
