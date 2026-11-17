<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\LessonProgress;
use App\Models\User;

/**
 * مدیریت ثبت‌نام دوره‌ها و محاسبه پیشرفت
 */
class CourseEnrollmentService
{
    public static function enroll(User $user, Course $course, string $source = 'manual', ?int $orderId = null): CourseEnrollment
    {
        \App\Models\AuditLog::record($user, 'course.enrolled', $course, ['source' => $source]);

        return CourseEnrollment::firstOrCreate(
            ['course_id' => $course->id, 'user_id' => $user->id],
            [
                'source' => $source,
                'order_id' => $orderId,
                'activated_at' => now(),
            ]
        );
    }

    /** محاسبه و ذخیره درصد پیشرفت دوره */
    public static function recomputeProgress(User $user, Course $course): int
    {
        $total = $course->lessons()->count();
        if ($total === 0) {
            return 0;
        }
        $done = LessonProgress::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->whereNotNull('completed_at')
            ->count();

        $progress = (int) round($done / $total * 100);

        CourseEnrollment::query()
            ->where('course_id', $course->id)
            ->where('user_id', $user->id)
            ->update(['progress' => $progress]);

        return $progress;
    }
}
