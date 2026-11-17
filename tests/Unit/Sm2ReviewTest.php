<?php

namespace Tests\Unit;

use App\Models\Review;
use App\Models\User;
use App\Services\ReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Sm2ReviewTest extends TestCase
{
    use RefreshDatabase;

    private ReviewService $service;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->service = app(ReviewService::class);
        $this->user = User::create([
            'name' => 'دانشجو تست',
            'email' => 'sm2-test@key.test',
            'password' => bcrypt('Admin@1234'),
            'is_active' => true,
        ]);
    }

    private function makeReview(): Review
    {
        return Review::create([
            'user_id' => $this->user->id,
            'reviewable_type' => \App\Models\Summary::class,
            'reviewable_id' => 1,
            'easiness' => 2.5,
            'interval_days' => 0,
            'repetitions' => 0,
            'lapses' => 0,
            'total_reviews' => 0,
            'due_at' => now(),
            'status' => 'due',
        ]);
    }

    public function test_first_good_answer_schedules_one_day(): void
    {
        $review = $this->makeReview();
        $this->service->schedule($review, ReviewService::QUALITY_GOOD);

        $this->assertEquals(1, $review->repetitions);
        $this->assertEquals(1, $review->interval_days);
        $this->assertTrue($review->due_at->isTomorrow());
        $this->assertDatabaseCount('review_logs', 1);
    }

    public function test_second_good_answer_schedules_six_days(): void
    {
        $review = $this->makeReview();
        $this->service->schedule($review, ReviewService::QUALITY_GOOD);
        $this->service->schedule($review, ReviewService::QUALITY_GOOD);

        $this->assertEquals(2, $review->repetitions);
        $this->assertEquals(6, $review->interval_days);
    }

    public function test_easy_answer_multiplies_interval(): void
    {
        $review = $this->makeReview();
        $this->service->schedule($review, ReviewService::QUALITY_GOOD);
        $this->service->schedule($review, ReviewService::QUALITY_GOOD);
        $before = $review->interval_days;
        $this->service->schedule($review, ReviewService::QUALITY_EASY);

        $this->assertGreaterThan($before, $review->interval_days);
        $this->assertGreaterThan(2.5, $review->easiness);
    }

    public function test_again_resets_cycle_and_counts_lapse(): void
    {
        $review = $this->makeReview();
        $this->service->schedule($review, ReviewService::QUALITY_GOOD);
        $this->service->schedule($review, ReviewService::QUALITY_GOOD);
        $this->service->schedule($review, ReviewService::QUALITY_AGAIN);

        $this->assertEquals(0, $review->repetitions);
        $this->assertEquals(1, $review->interval_days);
        $this->assertEquals(1, $review->lapses);
        $this->assertTrue($review->due_at->isToday() || $review->due_at->isTomorrow());
    }

    public function test_easiness_never_drops_below_floor(): void
    {
        $review = $this->makeReview();
        for ($i = 0; $i < 5; $i++) {
            $this->service->schedule($review, ReviewService::QUALITY_AGAIN);
        }
        $this->assertGreaterThanOrEqual(1.3, $review->easiness);
    }
}
