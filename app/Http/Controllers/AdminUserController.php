<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\CounselorStudent;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * مدیریت کاربران: فهرست/جست‌وجو/فیلتر، ساخت، ویرایش، غیرفعال‌سازی، تخصیص مشاور
 */
class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $roleSlug = (string) $request->query('role');
        $status = (string) $request->query('status');

        $users = User::query()
            ->with('roles')
            ->when($q !== '', fn ($query) => $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhere('national_code', 'like', "%{$q}%");
            }))
            ->when($roleSlug !== '', fn ($query) => $query->whereHas('roles', fn ($r) => $r->where('slug', $roleSlug)))
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($status === 'online', fn ($query) => $query->where('last_seen_at', '>=', now()->subMinutes(5)))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $roles = Role::orderBy('id')->get(['id', 'name', 'slug']);
        $onlineCount = User::query()->where('last_seen_at', '>=', now()->subMinutes(5))->count();

        return view('admin.users.index', compact('users', 'roles', 'q', 'roleSlug', 'status', 'onlineCount'));
    }

    public function create()
    {
        $roles = Role::orderBy('id')->get(['id', 'name', 'slug']);

        return view('admin.users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8'],
            'grade' => ['nullable', 'string', 'max:60'],
            'field_group' => ['nullable', 'in:math,science,humanities,language,arts'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'email.unique' => 'این ایمیل قبلاً ثبت شده است.',
            'phone.unique' => 'این شماره موبایل قبلاً ثبت شده است.',
            'password.min' => 'رمز عبور باید حداقل ۸ کاراکتر باشد.',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => ($data['phone'] ?? null) ?: null,
            'password' => $data['password'],
            'grade' => $data['grade'] ?? null,
            'field_group' => $data['field_group'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'email_verified_at' => now(),
        ]);

        $role = Role::findOrFail($data['role_id']);
        $user->roles()->sync([$role->id => ['assigned_by' => $request->user()->id, 'assigned_at' => now()]]);

        AuditLog::record($request->user(), 'admin.user.created', $user, ['role' => $role->slug]);

        return redirect()->route('admin.users.index')->with('success', 'کاربر «' . $user->name . '» با نقش ' . $role->name . ' ساخته شد.');
    }

    public function edit(Request $request, User $user)
    {
        $user->load('roles');
        $roles = Role::orderBy('id')->get(['id', 'name', 'slug']);
        $counselors = User::query()
            ->whereHas('roles', fn ($r) => $r->where('slug', 'counselor'))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
        $counselorLinks = CounselorStudent::query()
            ->where('student_id', $user->id)
            ->with('counselor:id,name,email')
            ->orderByDesc('id')
            ->get();

        return view('admin.users.edit', compact('user', 'roles', 'counselors', 'counselorLinks'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'grade' => ['nullable', 'string', 'max:60'],
            'field_group' => ['nullable', 'in:math,science,humanities,language,arts'],
            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => ['integer', 'exists:roles,id'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'email.unique' => 'این ایمیل قبلاً ثبت شده است.',
            'phone.unique' => 'این شماره موبایل قبلاً ثبت شده است.',
            'role_ids.required' => 'حداقل یک نقش باید انتخاب شود.',
        ]);

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => ($data['phone'] ?? null) ?: null,
            'grade' => $data['grade'] ?? null,
            'field_group' => $data['field_group'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }
        $user->save();

        $user->roles()->sync($data['role_ids']);

        AuditLog::record($request->user(), 'admin.user.updated', $user, [
            'roles' => Role::whereIn('id', $data['role_ids'])->pluck('slug')->all(),
            'is_active' => $user->is_active,
            'password_changed' => ! empty($data['password']),
        ]);

        return redirect()->route('admin.users.edit', $user)->with('success', 'اطلاعات کاربر با موفقیت به‌روزرسانی شد.');
    }

    /** غیرفعال‌سازی به‌جای حذف — حفظ یکپارچگی داده‌ها */
    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'نمی‌توانید حساب خودتان را غیرفعال کنید.');
        }

        $user->forceFill(['is_active' => false])->save();
        AuditLog::record($request->user(), 'admin.user.deactivated', $user);

        return redirect()->route('admin.users.index')->with('success', 'کاربر «' . $user->name . '» غیرفعال شد (حذف نشد).');
    }

    /** تخصیص دانشجو به مشاور */
    public function assignCounselor(Request $request)
    {
        $data = $request->validate([
            'student_id' => ['required', 'integer', 'exists:users,id'],
            'counselor_id' => ['required', 'integer', 'exists:users,id'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $student = User::findOrFail($data['student_id']);
        $counselor = User::findOrFail($data['counselor_id']);

        $exists = CounselorStudent::query()
            ->where('student_id', $student->id)
            ->where('counselor_id', $counselor->id)
            ->where('status', 'active')
            ->exists();

        if ($exists) {
            return back()->with('error', 'این دانشجو قبلاً به این مشاور تخصیص یافته است.');
        }

        CounselorStudent::create([
            'student_id' => $student->id,
            'counselor_id' => $counselor->id,
            'assigned_by' => $request->user()->id,
            'status' => 'active',
            'note' => $data['note'] ?? null,
        ]);

        AuditLog::record($request->user(), 'admin.counselor.assigned', $student, ['counselor_id' => $counselor->id]);

        return back()->with('success', 'دانشجو به مشاور «' . $counselor->name . '» تخصیص یافت.');
    }
}
