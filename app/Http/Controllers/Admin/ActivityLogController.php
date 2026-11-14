<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function __invoke(Request $request): View
    {
        $action = array_key_exists((string) $request->query('action'), ActivityLog::LABELS) ? $request->query('action') : null;

        return view('admin.activity-logs.index', [
            'action' => $action,
            'logs' => ActivityLog::with('user', 'subject')
                ->when($action, fn ($q) => $q->where('action', $action))
                ->latest('id')->paginate(30)->withQueryString(),
        ]);
    }
}
