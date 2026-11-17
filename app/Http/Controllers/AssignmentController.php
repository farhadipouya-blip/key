<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\AuditLog;
use App\Models\Course;
use App\Models\Media;
use Illuminate\Http\Request;

/**
 * مدیریت تکالیف + فایل پیوست (رسانه کتابخانه)
 */
class AssignmentController extends Controller
{
    private function courseOptions(): array
    {
        return Course::query()->orderBy('title')->pluck('title', 'id')->all();
    }

    public function index(Request $request)
    {
        $assignments = Assignment::query()
            ->with(['course:id,title'])
            ->withCount('submissions')
            ->when($request->filled('q'), fn ($q) => $q->where('title', 'like', '%' . $request->q . '%'))
            ->when($request->filled('course_id'), fn ($q) => $q->where('course_id', $request->course_id))
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        $stats = [
            'all' => Assignment::count(),
            'submissions' => \App\Models\AssignmentSubmission::count(),
            'pending' => \App\Models\AssignmentSubmission::where('status', 'submitted')->count(),
            'graded' => \App\Models\AssignmentSubmission::where('status', 'graded')->count(),
        ];

        return view('admin.academy.assignments.index', [
            'assignments' => $assignments,
            'stats' => $stats,
            'courses' => $this->courseOptions(),
        ]);
    }

    public function create()
    {
        return view('admin.academy.assignments.form', [
            'assignment' => new Assignment(['max_score' => 100]),
            'courses' => $this->courseOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateAssignment($request);

        $data['created_by'] = $request->user()->id;
        $data['max_score'] = $data['max_score'] ?? 100;

        if ($request->hasFile('attachment')) {
            $data['media_id'] = $this->storeAttachment($request);
        }

        $assignment = Assignment::create($data);

        AuditLog::record($request->user(), 'assignment.created', $assignment, ['title' => $assignment->title]);

        return redirect()->route('admin.assignments.index')
            ->with('success', 'تکلیف «' . $assignment->title . '» ایجاد شد.');
    }

    public function edit(Assignment $assignment)
    {
        $assignment->load('course:id,title');
        $attachment = $assignment->media_id ? Media::find($assignment->media_id) : null;

        return view('admin.academy.assignments.form', [
            'assignment' => $assignment,
            'attachment' => $attachment,
            'courses' => $this->courseOptions(),
        ]);
    }

    public function update(Request $request, Assignment $assignment)
    {
        $data = $this->validateAssignment($request);

        if ($request->hasFile('attachment')) {
            $data['media_id'] = $this->storeAttachment($request);
        } elseif ($request->boolean('remove_attachment')) {
            $data['media_id'] = null;
        }

        $assignment->update($data);

        AuditLog::record($request->user(), 'assignment.updated', $assignment, ['title' => $assignment->title]);

        return redirect()->route('admin.assignments.index')
            ->with('success', 'تکلیف «' . $assignment->title . '» به‌روزرسانی شد.');
    }

    public function destroy(Request $request, Assignment $assignment)
    {
        $title = $assignment->title;
        $assignment->delete();

        AuditLog::record($request->user(), 'assignment.deleted', null, ['title' => $title, 'id' => $assignment->id]);

        return redirect()->route('admin.assignments.index')
            ->with('success', 'تکلیف «' . $title . '» و ارسال‌های آن حذف شد.');
    }

    private function validateAssignment(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string', 'max:10000'],
            'course_id' => ['nullable', 'exists:courses,id'],
            'due_at' => ['nullable', 'date'],
            'max_score' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'attachment' => ['nullable', 'file', 'max:20480', 'mimes:pdf,doc,docx,zip,rar,png,jpg,jpeg,txt,mp3,mp4'],
        ], [
            'title.required' => 'عنوان تکلیف الزامی است.',
            'description.required' => 'شرح تکلیف الزامی است.',
            'course_id.exists' => 'دوره انتخابی معتبر نیست.',
            'due_at.date' => 'مهلت تحویل معتبر نیست.',
            'max_score.numeric' => 'حداکثر نمره باید عدد باشد.',
            'max_score.max' => 'حداکثر نمره نمی‌تواند بیشتر از ۹۹۹ باشد.',
            'attachment.mimes' => 'فرمت فایل پیوست مجاز نیست (pdf، word، zip، تصویر، متن، صوت یا ویدیو).',
            'attachment.max' => 'حجم فایل پیوست حداکثر ۲۰ مگابایت است.',
        ]);
    }

    /** ذخیره فایل پیوست در کتابخانه رسانه با دیسک public */
    private function storeAttachment(Request $request): int
    {
        $file = $request->file('attachment');
        $path = $file->store('uploads/assignments', 'public');

        $media = Media::create([
            'user_id' => $request->user()->id,
            'name' => $file->getClientOriginalName(),
            'file_name' => $file->getClientOriginalName(),
            'path' => $path,
            'disk' => 'public',
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'type' => Media::typeFromMime((string) $file->getClientMimeType()),
            'access' => 'public',
            'is_scanned' => true,
            'scan_result' => 'ok',
        ]);

        AuditLog::record($request->user(), 'media.uploaded', $media, ['name' => $media->name]);

        return $media->id;
    }
}
