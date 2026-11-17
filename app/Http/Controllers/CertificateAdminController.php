<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Course;
use App\Models\Certificate;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * مدیریت گواهی‌نامه‌ها: صدور تکی و گروهی، ابطال
 */
class CertificateAdminController extends Controller
{
    public function index(Request $request)
    {
        $certificates = Certificate::query()
            ->with(['user:id,name,email', 'course:id,title', 'issuer:id,name'])
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('title', 'like', '%' . $request->q . '%')
                ->orWhere('uuid', 'like', '%' . $request->q . '%')
                ->orWhereHas('user', fn ($u) => $u->where('name', 'like', '%' . $request->q . '%')
                    ->orWhere('email', 'like', '%' . $request->q . '%'))))
            ->when($request->filled('course_id'), fn ($q) => $q->where('course_id', $request->course_id))
            ->when($request->filled('state'), function ($q) use ($request) {
                if ($request->state === 'active') {
                    $q->whereNull('revoked_at');
                } elseif ($request->state === 'revoked') {
                    $q->whereNotNull('revoked_at');
                }
            })
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'all' => Certificate::count(),
            'active' => Certificate::whereNull('revoked_at')->count(),
            'revoked' => Certificate::whereNotNull('revoked_at')->count(),
            'users' => User::where('is_active', true)->count(),
        ];

        return view('admin.academy.certificates.index', [
            'certificates' => $certificates,
            'stats' => $stats,
            'courses' => Course::orderBy('title')->pluck('title', 'id')->all(),
        ]);
    }

    /** صدور گواهی‌نامه: تکی (user_id) یا گروهی (ایمیل‌های خط به خط) */
    public function issue(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'user_id' => ['nullable', 'exists:users,id'],
            'bulk_emails' => ['nullable', 'string', 'max:20000'],
            'course_id' => ['nullable', 'exists:courses,id'],
            'type' => ['required', Rule::in(['fa', 'en'])],
        ], [
            'title.required' => 'عنوان گواهی‌نامه الزامی است.',
            'user_id.exists' => 'کاربر انتخابی معتبر نیست.',
            'course_id.exists' => 'دوره انتخابی معتبر نیست.',
            'type.required' => 'زبان گواهی‌نامه الزامی است.',
            'type.in' => 'زبان گواهی‌نامه باید فارسی یا انگلیسی باشد.',
        ]);

        // تکی یا گروهی؟
        $emails = collect(preg_split('/[\r\n,;]+/u', (string) ($data['bulk_emails'] ?? '')))
            ->map(fn ($e) => trim($e))
            ->filter()
            ->unique()
            ->values();

        if ($emails->isNotEmpty()) {
            return $this->issueBulk($request, $data, $emails);
        }

        $request->validate(['user_id' => ['required', 'exists:users,id']], [
            'user_id.required' => 'انتخاب کاربر یا ورود ایمیل‌های گروهی الزامی است.',
        ]);

        $user = User::findOrFail($data['user_id']);
        $course = ! empty($data['course_id']) ? Course::find($data['course_id']) : null;

        $certificate = CertificateController::issue(
            $user,
            $data['title'],
            $course,
            null,
            $data['type'],
            $request->user()
        );

        return redirect()->route('admin.certificates.index')
            ->with('success', 'گواهی‌نامه برای «' . $user->name . '» صادر شد. کد یکتا: ' . $certificate->uuid);
    }

    /** صدور گروهی بر اساس ایمیل‌ها */
    private function issueBulk(Request $request, array $data, $emails)
    {
        $issued = 0;
        $notFound = [];
        $course = ! empty($data['course_id']) ? Course::find($data['course_id']) : null;

        foreach ($emails as $email) {
            $user = User::query()->where('email', $email)->first();
            if (! $user) {
                $notFound[] = $email;
                continue;
            }
            CertificateController::issue($user, $data['title'], $course, null, $data['type'], $request->user());
            $issued++;
        }

        $message = 'صدور گروهی انجام شد: ' . fa_num($issued) . ' گواهی‌نامه صادر شد.';
        if ($notFound) {
            $message .= ' ایمیل‌های ناموجود: ' . implode('، ', array_slice($notFound, 0, 8));
        }

        AuditLog::record($request->user(), 'certificate.bulk_issued', null, [
            'requested' => $emails->count(),
            'issued' => $issued,
            'title' => $data['title'],
        ]);

        return redirect()->route('admin.certificates.index')->with($notFound ? 'info' : 'success', $message);
    }

    /** ابطال گواهی‌نامه */
    public function revoke(Request $request, Certificate $certificate)
    {
        if ($certificate->isRevoked()) {
            return redirect()->route('admin.certificates.index')
                ->with('info', 'این گواهی‌نامه قبلاً ابطال شده است.');
        }

        $certificate->update(['revoked_at' => now()]);

        AuditLog::record($request->user(), 'certificate.revoked', $certificate, [
            'uuid' => $certificate->uuid,
            'title' => $certificate->title,
        ]);

        return redirect()->route('admin.certificates.index')
            ->with('success', 'گواهی‌نامه «' . $certificate->title . '» ابطال شد.');
    }
}
