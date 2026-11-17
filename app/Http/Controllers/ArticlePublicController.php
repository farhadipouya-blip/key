<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use App\Models\Comment;
use Illuminate\Http\Request;

/**
 * مقالات عمومی: فهرست، نمایش، ثبت دیدگاه (بدون نیاز به ورود برای مشاهده)
 */
class ArticlePublicController extends Controller
{
    /** فهرست مقالات منتشرشده و عمومی با جستجو و فیلتر دسته */
    public function index(Request $request)
    {
        $articles = Article::query()
            ->where('status', 'published')
            ->where('is_public', true)
            ->with(['author:id,name', 'category:id,title,slug'])
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('title', 'like', '%' . $request->q . '%')
                ->orWhere('excerpt', 'like', '%' . $request->q . '%')
                ->orWhere('content', 'like', '%' . $request->q . '%')))
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->category_id))
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(9)
            ->withQueryString();

        $categories = Category::query()->where('type', 'article')->orderBy('order')->get();

        $featured = Article::query()
            ->where('status', 'published')->where('is_public', true)
            ->orderByDesc('views')->take(4)->get();

        return view('articles.index', compact('articles', 'categories', 'featured'));
    }

    /** نمایش مقاله با افزایش شمارنده بازدید و دیدگاه‌های تأییدشده */
    public function show(Request $request, Article $article)
    {
        $isStaff = $request->user()?->isStaff();
        abort_unless(($article->status === 'published' && $article->is_public) || $isStaff, 404);

        Article::query()->whereKey($article->id)->increment('views');

        $article->load(['author:id,name', 'category:id,title']);
        $comments = $article->comments()
            ->with('user:id,name')
            ->where('status', 'approved')
            ->orderBy('id')
            ->get();

        $pendingCount = auth()->check()
            ? $article->comments()->where('user_id', auth()->id())->where('status', 'pending')->count()
            : 0;

        $related = Article::query()
            ->where('status', 'published')->where('is_public', true)
            ->where('id', '!=', $article->id)
            ->when($article->category_id, fn ($q) => $q->where('category_id', $article->category_id))
            ->latest('published_at')->take(3)->get();

        return view('articles.show', compact('article', 'comments', 'pendingCount', 'related'));
    }

    /** ثبت دیدگاه — فقط کاربران واردشده؛ نظر جدید با وضعیت در انتظار تأیید */
    public function comment(Request $request, Article $article)
    {
        abort_unless($article->status === 'published' && $article->is_public, 404);

        $user = $request->user();
        if (! $user) {
            return redirect()->route('login')->with('info', 'برای ثبت دیدگاه ابتدا وارد حساب خود شوید.');
        }

        $data = $request->validate([
            'body' => ['required', 'string', 'min:3', 'max:2000'],
        ], [
            'body.required' => 'متن دیدگاه الزامی است.',
            'body.min' => 'متن دیدگاه باید حداقل ۳ نویسه باشد.',
            'body.max' => 'متن دیدگاه حداکثر ۲۰۰۰ نویسه است.',
        ]);

        Comment::create([
            'user_id' => $user->id,
            'commentable_type' => $article->getMorphClass(),
            'commentable_id' => $article->id,
            'body' => trim($data['body']),
            'status' => 'pending',
        ]);

        \App\Models\AuditLog::record($user, 'comment.posted', $article, ['article' => $article->id]);

        return redirect()->route('articles.public.show', $article)
            ->with('success', 'دیدگاه شما ثبت شد و پس از تأیید مدیر نمایش داده می‌شود.');
    }
}
