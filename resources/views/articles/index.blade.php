@extends('layouts.public')

@section('title', 'مقالات آموزشی')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h4 class="fw-bold mb-0"><i class="bi bi-newspaper text-primary"></i> مقالات آموزشی</h4>
    <form method="GET" class="d-flex gap-2 flex-wrap">
        <select name="category_id" class="form-select form-select-sm" style="max-width:180px">
            <option value="">همه دسته‌ها</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @if (request('category_id') == $category->id) selected @endif>{{ $category->title }}</option>
            @endforeach
        </select>
        <input type="text" name="q" class="form-control form-control-sm" style="max-width:220px"
               placeholder="جستجوی مقاله..." value="{{ request('q') }}">
        <button class="btn btn-sm btn-primary"><i class="bi bi-search"></i> جستجو</button>
    </form>
</div>

@if ($featured->isNotEmpty() && ! request()->filled('q') && ! request()->filled('category_id'))
    <div class="row g-3 mb-4">
        @foreach ($featured->take(4) as $article)
            <div class="col-md-6">
                <a href="{{ route('articles.public.show', $article) }}" class="text-decoration-none text-body">
                    <div class="card h-100 border-warning">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="badge badge-soft-warning"><i class="bi bi-fire"></i> پربازدید</span>
                                <span class="small text-muted"><i class="bi bi-eye"></i> {{ fa_num($article->views) }} بازدید</span>
                            </div>
                            <h6 class="fw-bold">{{ $article->title }}</h6>
                            <p class="small text-muted mb-0">{{ \Illuminate\Support\Str::limit($article->excerpt ?: $article->content, 120) }}</p>
                        </div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>
@endif

<div class="row g-3">
    @forelse ($articles as $article)
        <div class="col-md-6 col-lg-4">
            <a href="{{ route('articles.public.show', $article) }}" class="text-decoration-none text-body">
                <div class="card h-100">
                    @if ($article->cover_path)
                        <img src="{{ storage_public_url($article->cover_path) }}" alt="{{ $article->title }}" class="card-img-top" style="height:160px;object-fit:cover">
                    @else
                        <div class="text-center py-4 bg-light">
                            <i class="bi bi-journal-text text-primary" style="font-size:2.6rem"></i>
                        </div>
                    @endif
                    <div class="card-body">
                        @if ($article->category)
                            <span class="badge badge-soft-info mb-2">{{ $article->category->title }}</span>
                        @endif
                        <h6 class="fw-bold">{{ $article->title }}</h6>
                        <p class="small text-muted mb-2">{{ \Illuminate\Support\Str::limit($article->excerpt ?: $article->content, 110) }}</p>
                        <div class="d-flex justify-content-between small text-muted">
                            <span><i class="bi bi-person"></i> {{ $article->author?->name ?? 'تحریریه کلید' }}</span>
                            <span><i class="bi bi-eye"></i> {{ fa_num($article->views) }}</span>
                        </div>
                        <div class="small text-muted mt-1">
                            @if ($article->published_at)
                                <i class="bi bi-calendar3"></i> {{ fa_date($article->published_at) }}
                            @endif
                        </div>
                    </div>
                </div>
            </a>
        </div>
    @empty
        <div class="col-12">
            <x-empty icon="bi-newspaper" title="مقاله‌ای یافت نشد"
                     message="با تغییر فیلترها جستجو کنید یا بعداً مراجعه نمایید."/>
        </div>
    @endforelse
</div>

<div class="mt-4">
    {{ $articles->links() }}
</div>
@endsection
