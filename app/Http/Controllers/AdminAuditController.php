<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * لاگ فعالیت سامانه با فیلتر کاربر/اکشن
 */
class AdminAuditController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $userId = (int) $request->query('user_id');
        $action = trim((string) $request->query('action'));

        $logs = AuditLog::query()
            ->with('user:id,name,email')
            ->when($userId > 0, fn ($query) => $query->where('user_id', $userId))
            ->when($action !== '', fn ($query) => $query->where('action', 'like', "%{$action}%"))
            ->when($q !== '', function ($query) use ($q) {
                $userIds = User::query()
                    ->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->pluck('id');

                $query->where(function ($w) use ($q, $userIds) {
                    $w->where('action', 'like', "%{$q}%")
                        ->orWhereIn('user_id', $userIds);
                });
            })
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $actions = AuditLog::query()->select('action')->distinct()->orderBy('action')->pluck('action');
        $users = User::query()->orderBy('name')->get(['id', 'name']);

        return view('admin.audit.index', compact('logs', 'actions', 'users', 'q', 'userId', 'action'));
    }
}
