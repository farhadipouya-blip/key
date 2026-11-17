<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\Review;
use App\Models\Summary;
use App\Services\ReviewService;
use Illuminate\Http\Request;

/**
 * ماژول مرور هوشمند (فاصله‌دار) بر پایه SM-2
 */
class ReviewController extends Controller
{
    public function __construct(private ReviewService $reviews) {}

    /** داشبورد مرور: آمار، صف امروز، ماندگاری */
    public function index(Request $request)
    {
        $user = $request->user();
        $due = $this->reviews->dailyQueue($user, 50);
        $stats = $this->reviews->retentionStats($user);
        $total = Review::query()->where('user_id', $user->id)->count();
        $upcoming = Review::query()
            ->where('user_id', $user->id)->where('status', 'active')
            ->where('due_at', '>', now())
            ->orderBy('due_at')->take(10)->get();

        return view('review.index', compact('due', 'stats', 'total', 'upcoming'));
    }

    /** شروع جلسه مرور: نمایش نوبت بعدی */
    public function session(Request $request)
    {
        $item = $this->reviews->dailyQueue($request->user(), 1)->first();

        if (! $item) {
            return redirect()->route('review.index')->with('info', 'صف مرور امروز خالی است. آفرین!');
        }

        return view('review.session', ['review' => $item]);
    }

    /** ثبت کیفیت یادآوری و رفتن به نوبت بعد */
    public function submit(Request $request)
    {
        $data = $request->validate([
            'quality' => ['required', 'in:1,2,3,4'],
        ]);

        $review = Review::query()->where('user_id', $request->user()->id)->findOrFail($request->input('review_id'));

        $this->reviews->schedule($review, (int) $data['quality']);

        return redirect()->route('review.session');
    }

    /** افزودن دستی آیتم به صف مرور */
    public function add(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', 'in:question,summary'],
            'id' => ['required', 'integer'],
        ]);

        if ($data['type'] === 'question') {
            $item = Question::findOrFail($data['id']);
        } else {
            $item = Summary::query()->where('is_public', true)->orWhere('user_id', $request->user()->id)->findOrFail($data['id']);
        }

        $this->reviews->ensureReview($request->user(), $item);

        return back()->with('success', 'به صف مرور اضافه شد.');
    }

    public function history(Request $request)
    {
        $logs = \App\Models\ReviewLog::query()
            ->where('user_id', $request->user()->id)
            ->with('review')
            ->latest('id')->paginate(20);

        return view('review.history', compact('logs'));
    }
}
