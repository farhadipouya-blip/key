<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;

/**
 * مدیریت نقش‌ها و مجوزها
 */
class AdminRoleController extends Controller
{
    public function index()
    {
        $roles = Role::query()->with(['permissions:id,slug,name,group'])->withCount('users')->orderBy('id')->get();
        $permissions = Permission::query()->orderBy('id')->get();

        return view('admin.roles.index', compact('roles', 'permissions'));
    }

    /** هم‌گام‌سازی مجوزهای یک نقش */
    public function update(Request $request, Role $role)
    {
        $data = $request->validate([
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['integer', 'exists:permissions,id'],
        ]);

        $role->permissions()->sync($data['permission_ids'] ?? []);

        AuditLog::record($request->user(), 'admin.role.permissions_updated', $role, [
            'permissions' => Permission::whereIn('id', $data['permission_ids'] ?? [])->pluck('slug')->all(),
        ]);

        return back()->with('success', 'مجوزهای نقش «' . $role->name . '» به‌روزرسانی شد.');
    }
}
