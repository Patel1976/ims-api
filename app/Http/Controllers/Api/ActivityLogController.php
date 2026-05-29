<?php

namespace App\Http\Controllers\Api;

use App\Models\ActivityLog;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function getActivityLogs(Request $request)
    {
        $request->validate([
            'search'    => 'nullable|string',
            'action'    => 'nullable|in:Add,Edit,Delete,View',
            'module'    => 'nullable|string',
            'from_date' => 'nullable|date',
            'to_date'   => 'nullable|date|after_or_equal:from_date',
        ]);

        $logs = ActivityLog::with('user')
            ->when($request->search, fn($q) => $q->where(function ($q) use ($request) {
                $q->where('description', 'like', "%{$request->search}%")
                  ->orWhereHas('user', fn($q) => $q->where('name', 'like', "%{$request->search}%"));
            }))
            ->when($request->action,    fn($q) => $q->where('action', $request->action))
            ->when($request->module,    fn($q) => $q->where('module', $request->module))
            ->when($request->from_date, fn($q) => $q->whereDate('created_at', '>=', $request->from_date))
            ->when($request->to_date,   fn($q) => $q->whereDate('created_at', '<=', $request->to_date))
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($log) => [
                'id'          => $log->id,
                'user'        => $log->user?->name ?? 'System',
                'action'      => $log->action,
                'module'      => $log->module,
                'description' => $log->description,
                'date'        => $log->created_at->format('Y-m-d'),
                'time'        => $log->created_at->format('H:i:s'),
            ]);

        return response()->json(['success' => 1, 'data' => $logs], 200);
    }

    public function clearActivityLogs()
    {
        ActivityLog::truncate();
        return response()->json(['success' => 1, 'message' => 'Activity logs cleared successfully'], 200);
    }
}
