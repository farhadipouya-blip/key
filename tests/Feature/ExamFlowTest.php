<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\Question;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    private Exam $exam;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->seed(\Database\Seeders\SettingSeeder::class);
        $this->seed(\Database\Seeders\DemoContentSeeder::class);

        $this->student = User::where('email', 'student@key.test')->firstOrFail();
        $this->exam = Exam::where('status', 'published')->firstOrFail();
    }

    public function test_student_sees_exam_hub(): void
    {
        $this->actingAs($this->student)->get(route('exams.hub'))->assertOk();
    }

    public function test_student_can_take_exam_and_get_report(): void
    {
        $this->actingAs($this->student);

        $this->post(route('exams.start', $this->exam))->assertRedirect();

        $attempt = \App\Models\ExamAttempt::query()
            ->where('user_id', $this->student->id)
            ->where('exam_id', $this->exam->id)
            ->where('status', 'in_progress')
            ->latest('id')->firstOrFail();

        // پاسخ به همه سؤال‌ها
        $questions = $this->exam->examQuestions()->with('question.options')->get();
        foreach ($questions as $eq) {
            $correct = $eq->question->options->firstWhere('is_correct', 1);
            $wrong = $eq->question->options->firstWhere('is_correct', 0);
            $option = ($eq->question->id % 2 === 0) ? $correct : $wrong;

            $this->post(route('exams.answer', [$attempt, $eq->question]), ['option_id' => $option->id])
                ->assertOk();
        }

        $this->post(route('exams.finish', $attempt))->assertRedirect();

        $attempt->refresh();
        $this->assertEquals('finished', $attempt->status);
        $this->assertGreaterThan(0, $attempt->correct_count);
        $this->assertGreaterThan(0, $attempt->wrong_count);
        $this->assertGreaterThan(0, $attempt->percent);

        // کارنامه
        $this->get(route('exams.result', $attempt))->assertOk();

        // سؤال‌های غلط باید به صف مرور اضافه شده باشند
        $wrongIds = \App\Models\AttemptAnswer::query()
            ->where('attempt_id', $attempt->id)
            ->where('is_correct', 0)
            ->pluck('question_id');

        $this->assertGreaterThan(0, $wrongIds->count());
        foreach ($wrongIds as $qid) {
            $this->assertDatabaseHas('reviews', [
                'user_id' => $this->student->id,
                'reviewable_type' => Question::class,
                'reviewable_id' => $qid,
            ]);
        }
    }

    public function test_cannot_start_unpublished_exam(): void
    {
        $draft = Exam::where('status', '!=', 'published')->first() ?? Exam::create([
            'title' => 'پیش‌نویس تست',
            'type' => 'custom',
            'duration_minutes' => 30,
            'status' => 'draft',
            'created_by' => $this->student->id,
        ]);

        $this->actingAs($this->student)
            ->post(route('exams.start', $draft))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_review_session_submission_reschedules(): void
    {
        $this->actingAs($this->student);

        $review = Review::create([
            'user_id' => $this->student->id,
            'reviewable_type' => Question::class,
            'reviewable_id' => 9999,
            'easiness' => 2.5,
            'interval_days' => 0,
            'repetitions' => 0,
            'lapses' => 0,
            'total_reviews' => 0,
            'due_at' => now()->subHour(),
            'status' => 'due',
        ]);

        $this->post(route('review.submit'), ['review_id' => $review->id, 'quality' => 3])
            ->assertRedirect();

        $review->refresh();
        $this->assertEquals(1, $review->repetitions);
        $this->assertEquals(1, $review->interval_days);
    }
}
