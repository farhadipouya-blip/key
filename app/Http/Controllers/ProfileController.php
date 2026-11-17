<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/** پروفایل کاربری و اعلان‌ها */
class ProfileController extends Controller
{
    public function edit()
    {
        return view('profile.edit');
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:190', 'unique:users,email,' . $user->id],
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone,' . $user->id],
            'bio' => ['nullable', 'string', 'max:1000'],
            'national_code' => ['nullable', 'string', 'max:15'],
            'field_group' => ['nullable', 'string', 'max:30'],
            'grade' => ['nullable', 'string', 'max:30'],
            'city' => ['nullable', 'string', 'max:60'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $data['avatar_path'] = $path;
        }

        $user->update($data);
        AuditLog::record($user, 'profile.updated', $user);

        return back()->with('success', 'پروفایل با موفقیت به‌روزرسانی شد.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required'],
            'password' => ['required', 'confirmed', 'min:8'],
        ], ['password.min' => 'رمز جدید حداقل ۸ کاراکتر باشد']);

        $user = $request->user();
        if (! Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'رمز فعلی نادرست است']);
        }

        $user->forceFill(['password' => Hash::make($request->password)])->save();
        AuditLog::record($user, 'profile.password_changed', $user);

        return back()->with('success', 'رمز عبور با موفقیت تغییر کرد.');
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();
        return back();
    }

    public function readOne(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->whereKey($id)->first();
        if ($notification) {
            $notification->markAsRead();
        }
        return back();
    }
}
