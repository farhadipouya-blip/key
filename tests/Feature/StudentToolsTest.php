<?php

namespace Tests\Feature;

use App\Models\Summary;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentToolsTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->seed(\Database\Seeders\SettingSeeder::class);
        $this->seed(\Database\Seeders\DemoContentSeeder::class);
        $this->student = User::where('email', 'student@key.test')->firstOrFail();
    }

    public function test_summary_crud_and_bookmark(): void
    {
        $this->actingAs($this->student);

        // ساخت
        $this->post(route('summaries.store'), [
            'title' => 'خلاصه تست یکپارچگی',
            'content' => 'متن خلاصه برای تست خودکار',
            'category' => 'خلاصه',
        ])->assertRedirect();

        $summary = Summary::where('user_id', $this->student->id)->latest('id')->firstOrFail();
        $this->assertFalse((bool) $summary->is_public);

        // ویرایش
        $this->put(route('summaries.update', $summary), [
            'title' => 'خلاصه ویرایش‌شده',
            'content' => 'متن ویرایش‌شده',
            'category' => 'نکته',
        ])->assertRedirect();

        $summary->refresh();
        $this->assertEquals('خلاصه ویرایش‌شده', $summary->title);

        // علامت‌گذاری
        $this->post(route('summaries.bookmark', $summary))->assertRedirect();
        $this->assertDatabaseHas('bookmarks', [
            'user_id' => $this->student->id,
            'bookmarkable_type' => Summary::class,
            'bookmarkable_id' => $summary->id,
        ]);

        // حذف علامت
        $this->post(route('summaries.bookmark', $summary))->assertRedirect();
        $this->assertDatabaseMissing('bookmarks', [
            'user_id' => $this->student->id,
            'bookmarkable_id' => $summary->id,
        ]);

        // حذف خلاصه
        $this->delete(route('summaries.destroy', $summary))->assertRedirect();
        $this->assertDatabaseMissing('summaries', ['id' => $summary->id]);
    }

    public function test_user_cannot_edit_others_summary(): void
    {
        $other = User::factory()->create();
        $other->roles()->sync([\App\Models\Role::where('slug', 'student')->first()->id]);

        $summary = Summary::create([
            'user_id' => $other->id,
            'title' => 'خلاصه دیگران',
            'content' => 'محتوای خصوصی',
            'is_public' => false,
        ]);

        $this->actingAs($this->student);
        $this->get(route('summaries.edit', $summary))->assertForbidden();
        $this->put(route('summaries.update', $summary), ['title' => 'hack', 'content' => 'x'])
            ->assertForbidden();
    }

    public function test_ticket_flow_from_student_and_support(): void
    {
        $admin = User::where('email', 'admin@key.test')->firstOrFail();

        $this->actingAs($this->student);
        $this->post(route('tickets.store'), [
            'subject' => 'مشکل در ورود به آزمون',
            'category' => 'فنی',
            'priority' => 'high',
            'body' => 'متن شرح مشکل برای تست خودکار',
        ])->assertRedirect();

        $ticket = Ticket::where('user_id', $this->student->id)->latest('id')->firstOrFail();
        $this->assertEquals('open', $ticket->status);

        $this->get(route('tickets.show', $ticket))->assertOk();

        // پاسخ پشتیبانی
        $this->actingAs($admin);
        $this->get(route('support.tickets.index'))->assertOk();
        $this->post(route('tickets.reply', $ticket), ['body' => 'پاسخ کارشناس'])
            ->assertRedirect();

        $ticket->refresh();
        $this->assertEquals('answered', $ticket->status);

        // بستن تیکت توسط دانشجو
        $this->actingAs($this->student);
        $this->post(route('tickets.close', $ticket))->assertRedirect();
        $ticket->refresh();
        $this->assertEquals('closed', $ticket->status);
    }
}
