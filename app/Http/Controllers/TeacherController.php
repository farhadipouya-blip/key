<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\AuditLog;
use App\Models\ClassAttendance;
use App\Models\Course;
use App\Models\LessonProgress;
use App\Models\OnlineClass;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * پنل مدرس: دوره‌های من، تصحیح تکالیف، کلاس‌های من، دفتر نمره
 * دسترسی فقط با مجوز academy.teach و برای دوره‌های خود مدرس
 */
class TeacherController extends Controller
{
    /** دوره‌های اختصاص‌یافته به مدرس */
    public function courses(Request $request)
    {
        $teacher = $request->user();

        $courses = Course::query()
            ->where('teacher_id', $teacher->id)
            ->with(['category:id,title'])
            ->withCount(['lessons', 'enrollments'])
            ->latest('id')
            ->get();

        $stats = [
            'courses' => $courses->count(),
            'students' => \App\Models\CourseEnrollment::whereIn('course_id', $courses->pluck('id'))->count(),
            'assignments' => Assignment::whereIn('course_id', $courses->pluck('id'))->count(),
            'classes' => OnlineClass::where('teacher_id', $teacher->id)->count(),
        ];

        return view('teacher.courses', compact('courses', 'stats'));
    }

    /** تکالیف مدرس + ارسال‌های دانشجویان با فرم نمره‌دهی */
    public function assignments(Request $request)
    {
        $teacher = $request->user();

        $myCourseIds = Course::where('teacher_id', $teacher->id)->pluck('id');

        $assignments = Assignment::query()
            ->where(fn ($q) => $q->whereIn('course_id', $myCourseIds)->orWhere('created_by', $teacher->id))
            ->with(['course:id,title,teacher_id'])
            ->with(['submissions' => fn ($q) => $q->with('user:id,name,email')->orderBy('submitted_at', 'desc')])
            ->withCount('submissions')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $stats = [
            'assignments' => Assignment::whereIn('course_id', $myCourseIds)->count(),
            'submissions' => AssignmentSubmission::whereIn('assignment_id', Assignment::whereIn('course_id', $myCourseIds)->pluck('id'))->count(),
            'pending' => AssignmentSubmission::where('status', 'submitted')
                ->whereIn('assignment_id', Assignment::whereIn('course_id', $myCourseIds)->pluck('id'))->count(),
            'graded' => AssignmentSubmission::whereIn('assignment_id', Assignment::whereIn('course_id', $myCourseIds)->pluck('id'))
                ->where('status', 'graded')->count(),
        ];

        return view('teacher.assignments', compact('assignments', 'stats'));
    }

    /** ثبت/به‌روزرسانی نمره ارسال تکلیف (فقط مدرس صاحب دوره) */
    public function grade(Request $request, AssignmentSubmission $submission)
    {
        $assignment = $submission->assignment()->with('course')->first();
        $teacher = $request->user();

        $ownsCourse = $assignment->course && $assignment->course->teacher_id === $teacher->id;
        $ownsAssignment = (int) $assignment->created_by === (int) $teacher->id;

        abort_unless($ownsCourse || $ownsAssignment || $teacher->canAccess('academy.manage'), 403,
            'فقط مدرس دوره یا مدیر سامانه می‌تواند این ارسال را تصحیح کند.');

        $data = $request->validate([
            'score' => ['required', 'numeric', 'min:0', 'max:' . (float) $assignment->max_score],
            'feedback' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', 'in:graded,returned'],
        ], [
            'score.required' => 'ثبت نمره الزامی است.',
            'score.numeric' => 'نمره باید عدد باشد.',
            'score.min' => 'نمره نمی‌تواند منفی باشد.',
            'score.max' => 'نمره نمی‌تواند بیشتر از حداکثر نمره تکلیف (' . fa_num($assignment->max_score) . ') باشد.',
            'feedback.max' => 'بازخورد حداکثر ۵۰۰۰ نویسه است.',
            'status.required' => 'وضعیت تصحیح الزامی است.',
            'status.in' => 'وضعیت باید تصحیح‌شده یا برگشتی باشد.',
        ]);

        $submission->update([
            'score' => $data['score'],
            'feedback' => $data['feedback'] ?? null,
            'status' => $data['status'],
            'graded_by' => $teacher->id,
            'graded_at' => now(),
        ]);

        AuditLog::record($teacher, 'assignment.graded', $submission, [
            'assignment' => $assignment->id,
            'student' => $submission->user_id,
            'score' => $data['score'],
        ]);

        return redirect()->back()
            ->with('success', 'نمره «' . $submission->user->name . '» برای تکلیف «' . $assignment->title . '» ثبت شد.');
    }

    /** کلاس‌های آنلاین مدرس + فهرست حاضران */
    public function classes(Request $request)
    {
        $teacher = $request->user();

        $classes = OnlineClass::query()
            ->where('teacher_id', $teacher->id)
            ->with(['course:id,title'])
            ->with(['attendances' => fn ($q) => $q->with('user:id,name,email')->orderBy('joined_at')])
            ->withCount('attendances')
            ->orderByDesc('starts_at')
            ->get();

        $stats = [
            'all' => $classes->count(),
            'upcoming' => $classes->where('starts_at', '>', now())->where('status', 'scheduled')->count(),
            'attendances' => ClassAttendance::whereIn('class_id', $classes->pluck('id'))->count(),
        ];

        return view('teacher.classes', compact('classes', 'stats'));
    }

    /** دفتر نمره: هر دوره → دانشجویان × پیشرفت دروس + میانگین نمرات تکالیف */
    public function gradebook(Request $request)
    {
        $teacher = $request->user();

        $courses = Course::query()
            ->where('teacher_id', $teacher->id)
            ->withCount('lessons')
            ->orderBy('title')
            ->get();

        $gradebooks = $courses->map(function (Course $course) {
            $lessonCount = (int) $course->lessons_count;

            $enrollments = $course->enrollments()
                ->with('user:id,name,email')
                ->orderBy('id')
                ->get();

            $assignmentIds = Assignment::where('course_id', $course->id)->pluck('id');

            $scores = AssignmentSubmission::query()
                ->whereIn('assignment_id', $assignmentIds)
                ->where('status', 'graded')
                ->selectRaw('user_id, avg(score) as avg_score, count(*) as graded_count')
                ->groupBy('user_id')
                ->pluck('avg_score', 'user_id');

            $gradedCounts = AssignmentSubmission::query()
                ->whereIn('assignment_id', $assignmentIds)
                ->where('status', 'graded')
                ->selectRaw('user_id, count(*) as c')
                ->groupBy('user_id')
                ->pluck('c', 'user_id');

            $doneCounts = LessonProgress::query()
                ->where('course_id', $course->id)
                ->whereNotNull('completed_at')
                ->selectRaw('user_id, count(*) as done')
                ->groupBy('user_id')
                ->pluck('done', 'user_id');

            $rows = $enrollments->map(function ($enrollment) use ($scores, $gradedCounts, $doneCounts, $lessonCount) {
                $done = (int) ($doneCounts[$enrollment->user_id] ?? 0);
                return [
                    'user' => $enrollment->user,
                    'enrollment' => $enrollment,
                    'done_lessons' => $done,
                    'lesson_count' => $lessonCount,
                    'progress' => $enrollment->progress,
                    'avg_score' => $scores[$enrollment->user_id] ?? null,
                    'graded_count' => (int) ($gradedCounts[$enrollment->user_id] ?? 0),
                ];
            });

            return ['course' => $course, 'rows' => $rows];
        });

        return view('teacher.gradebook', compact('gradebooks'));
    }
}
