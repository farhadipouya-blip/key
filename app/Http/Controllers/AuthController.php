<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * احراز هویت: ورود با ایمیل یا شماره موبایل، ثبت‌نام، بازیابی رمز
 */
class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect(Auth::user()->dashboardRoute());
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'identifier' => ['required', 'string', 'max:190'],
            'password' => ['required', 'string'],
        ], [
            'identifier.required' => 'ایمیل یا شماره موبایل الزامی است',
            'password.required' => 'رمز عبور الزامی است',
        ]);

        $identifier = \App\Support\Jalali::toLatinDigits(trim($data['identifier']));
        $field = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        $remember = $request->boolean('remember');

        $user = User::where($field, $identifier)->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return back()->withErrors(['identifier' => 'اطلاعات ورود نادرست است'])->onlyInput('identifier');
        }

        if (! $user->is_active) {
            return back()->withErrors(['identifier' => 'حساب شما غیرفعال است. با پشتیبانی تماس بگیرید.']);
        }

        Auth::login($user, $remember);
        $user->forceFill(['last_login_at' => now()])->saveQuietly();
        AuditLog::record($user, 'auth.login', $user);
        $request->session()->regenerate();

        return redirect()->intended($user->dashboardRoute());
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'string', 'email', 'max:190', 'unique:users,email'],
            'phone' => ['required', 'string', 'regex:/^09\d{9}$/u', 'unique:users,phone'],
            'password' => ['required', 'confirmed', 'min:8'],
            'field_group' => ['nullable', 'string', 'max:30'],
            'grade' => ['nullable', 'string', 'max:30'],
        ], [
            'phone.regex' => 'شماره موبایل باید به شکل 09xxxxxxxxx باشد',
            'phone.unique' => 'این شماره قبلاً ثبت شده است',
            'password.min' => 'رمز عبور حداقل ۸ کاراکتر باشد',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'],
            'password' => $data['password'],
            'field_group' => $data['field_group'] ?? null,
            'grade' => $data['grade'] ?? null,
            'is_active' => true,
        ]);

        $studentRole = Role::where('slug', 'student')->first();
        if ($studentRole) {
            $user->roles()->syncWithoutDetaching([$studentRole->id]);
        }

        // پیامک خوش‌آمدگویی ثبت‌نام
        app(SmsService::class)->send(
            $user->phone,
            'ثبت‌نام شما در سامانه کلید با موفقیت انجام شد. به خانواده کلید خوش آمدید!',
            $user->id,
            'register'
        );

        AuditLog::record($user, 'auth.register', $user);
        Auth::login($user);
        $request->session()->regenerate();

        return redirect($user->dashboardRoute());
    }

    public function logout(Request $request)
    {
        AuditLog::record($request->user(), 'auth.logout', $request->user());
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function showForgot()
    {
        return view('auth.forgot-password');
    }

    public function sendReset(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);
        $status = Password::sendResetLink($request->only('email'));
        return $status === Password::RESET_LINK_SENT
            ? back()->with('success', 'لینک بازیابی رمز ارسال شد (در حالت آزمایشی در فایل لاگ سامانه ثبت می‌شود).')
            : back()->withErrors(['email' => __($status)]);
    }

    public function showReset(string $token)
    {
        return view('auth.reset-password', ['token' => $token]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ], ['password.min' => 'رمز عبور حداقل ۸ کاراکتر باشد']);

        $status = Password::reset($request->only('email', 'password', 'password_confirmation', 'token'), function ($user, $password) {
            $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
            AuditLog::record($user, 'auth.password_reset', $user);
        });

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('success', 'رمز عبور با موفقیت تغییر کرد. وارد شوید.')
            : back()->withErrors(['email' => __($status)]);
    }
}
