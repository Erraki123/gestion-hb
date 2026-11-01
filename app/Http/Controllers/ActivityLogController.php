<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $type   = $request->input('subject_type');
        $action = $request->input('action');

        $logs = ActivityLog::with('user')
            ->when($request->input('user_id'), fn ($q, $id) => $q->where('user_id', $id))
            ->when($action && isset(ActivityLogger::ACTIONS[$action]), fn ($q) => $q->where('action', $action))
            ->when($type && isset(ActivityLogger::SUBJECTS[$type]), fn ($q) => $q->where('subject_type', $type))
            ->when($request->input('from'), fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->input('to'), fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->when($request->input('q'), fn ($q, $s) => $q->where(function ($w) use ($s) {
                $w->where('description', 'like', "%{$s}%")
                  ->orWhere('subject_label', 'like', "%{$s}%")
                  ->orWhere('user_name', 'like', "%{$s}%");
            }))
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        $users = User::withTrashed()->orderBy('name')->get(['id', 'name']);

        return view('activity.index', compact('logs', 'users'));
    }
}