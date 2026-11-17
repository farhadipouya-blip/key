<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Http\Request;

/**
 * گواهینامه: صدور، چاپ، استعلام عمومی با QR
 */
class CertificateController extends Controller
{
    public function index(Request $request)
    {
        $certificates = $request->user()->certificates()->with('course')->latest('id')->paginate(12);
        return view('certificates.index', compact('certificates'));
    }

    /** چاپ/دانلود گواهینامه (نسخه فارسی یا انگلیسی) */
    public function show(Request $request, Certificate $certificate)
    {
        if ($certificate->user_id !== $request->user()->id && ! $request->user()->isStaff()) {
            abort(403);
        }
        return view('certificates.print', compact('certificate'));
    }

    public function verifyForm()
    {
        return view('public.verify-certificate');
    }

    public function verify(Request $request)
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:64']]);
        $certificate = Certificate::query()
            ->where('uuid', trim($data['code']))
            ->with(['user', 'course'])
            ->first();

        return view('public.verify-certificate', ['code' => $data['code'], 'certificate' => $certificate]);
    }

    public function publicShow(Certificate $certificate)
    {
        $certificate->load(['user', 'course']);
        return view('public.certificate-verify-result', compact('certificate'));
    }

    /** تولید SVG کد QR برای استعلام */
    public static function qrSvg(string $url): string
    {
        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_MARKUP_SVG,
            'eccLevel' => QRCode::ECC_L,
            'scale' => 5,
            'svgQuietZone' => true,
        ]);
        return (new QRCode($options))->render($url);
    }

    /** صدور گواهینامه (توسط مدیریت/پس از اتمام دوره) */
    public static function issue($user, string $title, ?\App\Models\Course $course = null, ?int $attemptId = null, string $type = 'fa', $issuedBy = null): Certificate
    {
        $certificate = Certificate::create([
            'user_id' => $user->id,
            'title' => $title,
            'course_id' => $course?->id,
            'exam_attempt_id' => $attemptId,
            'type' => $type,
            'uuid' => Certificate::generateUuid(),
            'data' => [
                'student_name' => $user->name,
                'course_title' => $course?->title,
                'issue_date_fa' => fa_date(now()),
                'issue_date_en' => now()->format('Y-m-d'),
                'site_name' => 'کلید (KEY)',
            ],
            'issued_by' => $issuedBy?->id,
        ]);

        \App\Models\AuditLog::record($issuedBy ?? $user, 'certificate.issued', $certificate, ['title' => $title]);

        return $certificate;
    }
}
