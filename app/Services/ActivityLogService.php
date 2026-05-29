<?php

namespace App\Services;

use App\Models\ActivityLog;
use Tymon\JWTAuth\Facades\JWTAuth;

class ActivityLogService
{
    public static function log(string $action, string $module, string $description): void
    {
        try {
            $userId = JWTAuth::parseToken()->authenticate()?->id;
        } catch (\Exception) {
            $userId = null;
        }

        ActivityLog::create([
            'user_id'     => $userId,
            'action'      => $action,
            'module'      => $module,
            'description' => $description,
        ]);
    }
}
