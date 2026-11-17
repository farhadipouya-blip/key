<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\Question;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * آزمون‌های ماژول محتوای آموزشی: درس و مبحث، بانک سؤال، آزمون‌ساز
 */
class AdminContentTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->seed(\Database\Seeders\SettingSeeder::class);
        $this->seed(\Database\Seeders\DemoContentSeeder::class);

        $this->admin = User::where('email', 'admin@key.test')->firstOrFail();
        $this->student = User::where('email', 'student@key.test')->firstOrFail();
    }

    // ------------------------------------------------------------------
    // درس و مبحث
    // ------------------------------------------------------------------

    public function test_admin_sees_subject_tree_page(): void
    {
        $this->actingAs($this->admin)->get(route('admin.subjects.index'))->assertOk();
    }

    public function test_subject_level_is_resolved_from_parent(): void
    {
        $course = Subject::where('level', 'root')->firstOrFail();

        $this->actingAs($this->admin)
            ->post(route('admin.subjects.store'), ['title' => 'فصل جدید تست', 'parent_id' => $course->id])
            ->assertRedirect();

        $chapter = Subject::where('title', 'فصل جدید تست')->firstOrFail();
        $this->assertEquals('chapter', $chapter->level);
        $this->assertEquals($course->id, $chapter->parent_id);

        // مبحث زیر فصل
        $this->post(route('admin.subjects.store'), ['title' => 'مبحث جدید تست', 'parent_id' => $chapter->id])
            ->assertRedirect();
        $this->assertDatabaseHas('subjects', ['title' => 'مبحث جدید تست', 'level' => 'lesson']);
    }

    public function test_cannot_nest_subject_under_lesson(): void
    {
        $lesson = Subject::where('level', 'lesson')->firstOrFail();

        $this->actingAs($this->admin)
            ->post(route('admin.subjects.store'), ['title' => 'غیرمجاز', 'parent_id' => $lesson->id])
            ->assertSessionHasErrors();
    }

    public function test_student_cannot_access_subject_admin(): void
    {
        $this->actingAs($this->student)
            ->get(route('admin.subjects.index'))
            ->assertForbidden();
    }

    // ------------------------------------------------------------------
    // بانک سؤال
    // ------------------------------------------------------------------

    public function test_admin_can_create_multi_question_with_options(): void
    {
        $subject = Subject::firstOrFail();

        $payload = [
            'subject_id' => $subject->id,
            'type' => 'multi',
            'difficulty' => 'easy',
            'text' => 'حاصل $2+2\times3$ چند است؟',
            'explanation' => 'اولویت عملگرها',
            'default_marks' => 1,
            'is_active' => '1',
            'option_texts' => [1 => '۸', 2 => '۱۰', 3 => '۱۲', 4 => '۶'],
            'correct_option' => 1,
        ];

        $this->actingAs($this->admin)
            ->post(route('admin.questions.store'), $payload)
            ->assertRedirect();

        $question = Question::where('text', 'like', '%2+2%')->latest('id')->firstOrFail();
        $this->assertEquals(4, $question->options()->count());

        $correct = $question->options()->where('is_correct', true)->first();
        $this->assertNotNull($correct);
        $this->assertEquals(1, $correct->order);
    }

    public function test_multi_question_requires_correct_option(): void
    {
        $subject = Subject::firstOrFail();

        $payload = [
            'subject_id' => $subject->id,
            'type' => 'multi',
            'difficulty' => 'easy',
            'text' => 'سؤال بدون گزینه صحیح',
            'default_marks' => 1,
            'option_texts' => [1 => 'الف', 2 => 'ب', 3 => 'ج', 4 => 'د'],
            // correct_option ارسال نشده
        ];

        $this->actingAs($this->admin)
            ->post(route('admin.questions.store'), $payload)
            ->assertSessionHasErrors(['correct_option']);
    }

    public function test_descriptive_question_has_no_options(): void
    {
        $subject = Subject::firstOrFail();

        $payload = [
            'subject_id' => $subject->id,
            'type' => 'descriptive',
            'difficulty' => 'medium',
            'text' => 'قانون دوم نیوتن را توضیح دهید.',
            'answer_text' => 'F = ma',
            'default_marks' => 2,
            'is_active' => '1',
        ];

        $this->actingAs($this->admin)
            ->post(route('admin.questions.store'), $payload)
            ->assertRedirect();

        $question = Question::where('type', 'descriptive')->latest('id')->firstOrFail();
        $this->assertEquals(0, $question->options()->count());
    }

    public function test_question_toggle_switches_active_state(): void
    {
        $question = Question::where('is_active', true)->firstOrFail();

        $this->actingAs($this->admin)
            ->post(route('admin.questions.toggle', $question))
            ->assertRedirect();

        $this->assertFalse($question->fresh()->is_active);
    }

    // ------------------------------------------------------------------
    // آزمون‌ساز
    // ------------------------------------------------------------------

    public function test_admin_creates_exam_and_publishes_it(): void
    {
        $subject = Subject::firstOrFail();

        $this->actingAs($this->admin)
            ->post(route('admin.exams.store'), [
                'title' => 'آزمون تست سازنده',
                'type' => 'timed',
                'duration_minutes' => 45,
                'negative_marking' => '1',
                'negative_value' => 0.25,
                'is_public' => '1',
                'pass_percent' => 50,
            ])
            ->assertRedirect();

        $exam = Exam::where('title', 'آزمون تست سازنده')->firstOrFail();
        $this->assertEquals('draft', $exam->status);

        // انتشار بدون سؤال باید خطا دهد
        $this->post(route('admin.exams.status', $exam), ['status' => 'published'])
            ->assertRedirect()
            ->assertSessionHas('error');

        // افزودن سؤال از بانک
        $questionIds = Question::where('is_active', true)->take(3)->pluck('id')->all();
        $this->post(route('admin.exams.questions.attach', $exam), ['question_ids' => $questionIds])
            ->assertRedirect();

        $this->assertEquals(3, $exam->examQuestions()->count());
        $this->assertGreaterThan(0, $exam->fresh()->total_marks);

        // انتشار
        $this->post(route('admin.exams.status', $exam), ['status' => 'published'])->assertRedirect();
        $this->assertEquals('published', $exam->fresh()->status);

        // آزمون عمومی منتشرشده باید در مرکز آزمون دانشجو دیده شود
        $response = $this->actingAs($this->student)->get(route('exams.hub'));
        $response->assertOk();
        $response->assertSee('آزمون تست سازنده');
    }

    public function test_auto_generate_adds_unique_questions(): void
    {
        $subject = Subject::firstOrFail();

        $exam = Exam::create([
            'title' => 'آزمون خودکار',
            'type' => 'practice',
            'status' => 'draft',
            'is_public' => true,
            'created_by' => $this->admin->id,
        ]);

        $bankCount = Question::where('is_active', true)->whereIn('subject_id', [$subject->id])
            ->orWhereIn('subject_id', Subject::where('parent_id', $subject->id)->select('id'))
            ->count();

        if ($bankCount < 1) {
            $this->markTestSkipped('بانک سؤال نمونه خالی است.');
        }

        // درخت سه‌سطحی: درس + فصل‌ها + مباحث (مطابق منطق کنترلر)
        $ids = collect([$subject->id])
            ->concat($chapterIds = Subject::where('parent_id', $subject->id)->pluck('id'))
            ->concat($chapterIds ? Subject::whereIn('parent_id', $chapterIds)->pluck('id') : collect())
            ->all();

        $this->actingAs($this->admin)
            ->post(route('admin.exams.questions.generate', $exam), [
                'subject_ids' => [$subject->id],
                'count' => 100, // بیش از بانک → فقط همه بانک این درس اضافه می‌شود
            ])
            ->assertRedirect();

        $availableInTree = Question::where('is_active', true)->whereIn('subject_id', $ids)->count();
        $this->assertEquals($availableInTree, $exam->examQuestions()->count());

        // اجرای دوم سؤال تکراری اضافه نمی‌کند
        $before = $exam->examQuestions()->count();
        $this->post(route('admin.exams.questions.generate', $exam), [
            'subject_ids' => [$subject->id],
            'count' => 10,
        ])->assertRedirect();

        $this->assertEquals($before, $exam->examQuestions()->count());
    }

    public function test_exam_question_marks_and_order_update(): void
    {
        $exam = Exam::whereHas('examQuestions')->firstOrFail();

        $eqs = $exam->examQuestions()->orderBy('order')->get();
        $first = $eqs->first();
        $second = $eqs->skip(1)->first();
        $firstOrder = $first->order;
        $secondOrder = $second->order;

        $this->actingAs($this->admin);

        // جابه‌جایی به پایین: جای اول و دوم عوض می‌شود
        $this->put(route('admin.exams.questions.update', [$exam, $first]), ['move' => 'down'])->assertRedirect();

        $this->assertEquals($secondOrder, $first->fresh()->order);
        $this->assertEquals($firstOrder, $second->fresh()->order);

        // تغییر بارم
        $this->put(route('admin.exams.questions.update', [$exam, $first]), ['marks' => 2.5])->assertRedirect();
        $this->assertEquals(2.5, (float) $first->fresh()->marks);

        // مجموع بارم به‌روزرسانی شده است
        $expectedTotal = (float) $exam->examQuestions()->sum('marks');
        $this->assertEquals($expectedTotal, (float) $exam->fresh()->total_marks);
    }

    public function test_publishing_exam_without_questions_is_blocked(): void
    {
        $exam = Exam::create([
            'title' => 'بدون سؤال',
            'type' => 'timed',
            'status' => 'draft',
            'is_public' => true,
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.exams.status', $exam), ['status' => 'published'])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertEquals('draft', $exam->fresh()->status);
    }

    public function test_exam_builder_page_renders(): void
    {
        $exam = Exam::whereHas('examQuestions')->firstOrFail();

        $this->actingAs($this->admin)
            ->get(route('admin.exams.builder', $exam))
            ->assertOk()
            ->assertSee('بانک سؤال');
    }
}
