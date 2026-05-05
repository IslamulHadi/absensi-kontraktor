<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\AppPlatform;
use App\Http\Controllers\Controller;
use App\Models\AppVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppVersionController extends Controller
{
    public function latest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'platform' => ['nullable', 'string', 'in:android,ios'],
        ]);

        $platform = AppPlatform::from($validated['platform'] ?? AppPlatform::Android->value);

        $version = AppVersion::query()
            ->where('platform', $platform->value)
            ->where('is_active', true)
            ->orderByDesc('version_code')
            ->first();

        if ($version === null) {
            return response()->json([
                'message' => 'Tidak ada versi aktif untuk platform '.$platform->label().'.',
            ], 404);
        }

        return response()->json([
            'data' => [
                'platform' => $version->platform->value,
                'version_name' => $version->version_name,
                'version_code' => $version->version_code,
                'min_supported_version_code' => $version->min_supported_version_code,
                'download_url' => $version->download_url,
                'release_notes' => $version->release_notes,
                'released_at' => $version->released_at?->toIso8601String(),
            ],
        ]);
    }
}
