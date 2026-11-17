<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\AuditLog;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * مدیریت مقالات (تولید محتوا)
 */
class ArticleAdminController extends Controller
{
    private function articleCategories(): array
    {
        return Category::query()->where('type', 'article')->orderBy('order')->pluck('title', 'id')->all();
    }

    public function index(Request $request)
    {
        $articles = Article::query()
            ->with(['author:id,name', 'category:id,title'])
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('title', 'like', '%' . $request->q . '%')
                ->orWhere('excerpt', 'like', '%' . $request->q . '%')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->category_id))
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        $stats = [
            'all' => Article::count(),
            'published' => Article::where('status', 'published')->count(),
            'draft' => Article::where('status', 'draft')->count(),
            'views' => (int) Article::sum('views'),
        ];

        return view('admin.academy.articles.index', [
            'articles' => $articles,
            'stats' => $stats,
            'categories' => $this->articleCategories(),
        ]);
    }

    public function create()
    {
        return view('admin.academy.articles.form', [
            'article' => new Article(['status' => 'draft', 'is_public' => true]),
            'categories' => $this->articleCategories(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateArticle($request);

        $data['author_id'] = $request->user()->id;
        $data['is_public'] = $request->boolean('is_public');
        $data['slug'] = $this->uniqueSlug($data['title'], $request->input('slug'));

        if ($request->hasFile('cover')) {
            $data['cover_path'] = $request->file('cover')->store('uploads/articles', 'public');
        }

        // تاریخ انتشار: اگر منتشر شد و تاریخ ندارد، اکنون
        if ($data['status'] === 'published' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        $article = Article::create($data);

        AuditLog::record($request->user(), 'article.created', $article, ['title' => $article->title]);

        return redirect()->route('admin.articles.edit', $article)
            ->with('success', 'مقاله «' . $article->title . '» ایجاد شد.');
    }

    public function edit(Article $article)
    {
        return view('admin.academy.articles.form', [
            'article' => $article,
            'categories' => $this->articleCategories(),
        ]);
    }

    public function update(Request $request, Article $article)
    {
        $data = $this->validateArticle($request, $article);

        $data['is_public'] = $request->boolean('is_public');

        if ($request->hasFile('cover')) {
            $data['cover_path'] = $request->file('cover')->store('uploads/articles', 'public');
        }

        $newSlug = $this->uniqueSlug($data['title'], $request->input('slug'), $article->id);
        if ($newSlug !== $article->slug) {
            $data['slug'] = $newSlug;
        }

        if ($data['status'] === 'published' && empty($data['published_at']) && ! $article->published_at) {
            $data['published_at'] = now();
        }

        $article->update($data);

        AuditLog::record($request->user(), 'article.updated', $article, ['title' => $article->title]);

        return redirect()->route('admin.articles.edit', $article)
            ->with('success', 'مقاله «' . $article->title . '» به‌روزرسانی شد.');
    }

    public function destroy(Request $request, Article $article)
    {
        $title = $article->title;
        $article->delete();

        AuditLog::record($request->user(), 'article.deleted', null, ['title' => $title, 'id' => $article->id]);

        return redirect()->route('admin.articles.index')
            ->with('success', 'مقاله «' . $title . '» حذف شد.');
    }

    private function validateArticle(Request $request, ?Article $article = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:220', 'alpha_dash'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'string', 'max:100000'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'is_public' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'date'],
            'meta_title' => ['nullable', 'string', 'max:200'],
            'meta_description' => ['nullable', 'string', 'max:300'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ], [
            'title.required' => 'عنوان مقاله الزامی است.',
            'content.required' => 'متن مقاله الزامی است.',
            'slug.alpha_dash' => 'نامک فقط می‌تواند شامل حروف لاتین، عدد، خط تیره و زیرخط باشد.',
            'slug.max' => 'نامک حداکثر ۲۲۰ نویسه است.',
            'category_id.exists' => 'دسته انتخابی معتبر نیست.',
            'status.required' => 'انتخاب وضعیت الزامی است.',
            'status.in' => 'وضعیت باید پیش‌نویس یا منتشرشده باشد.',
            'published_at.date' => 'تاریخ انتشار معتبر نیست.',
            'excerpt.max' => 'خلاصه حداکثر ۵۰۰ نویسه است.',
            'cover.image' => 'فایل جلد باید تصویر باشد.',
            'cover.mimes' => 'فرمت تصویر جلد باید jpg، png یا webp باشد.',
            'cover.max' => 'حجم تصویر جلد حداکثر ۴ مگابایت است.',
        ]);

        $data['published_at'] = $data['published_at'] ?? ($article?->published_at?->format('Y-m-d\TH:i') ?: null);

        return $data;
    }

    /** ساخت نامک یکتا (فارسی → کد تصادفی خوانا) */
    private function uniqueSlug(string $title, ?string $requested = null, ?int $ignoreId = null): string
    {
        $base = $requested ?: (\Illuminate\Support\Str::slug($title) ?: 'article-' . \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(6)));
        $slug = $base;
        $i = 1;
        while (Article::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base . '-' . (++$i);
        }
        return $slug;
    }
}
