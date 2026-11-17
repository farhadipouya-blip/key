@extends('layouts.app')

@section('title', 'مدیریت مقالات')
@section('page_title', 'مدیریت مقالات')

@section('content')
<div class="row g-3 mb-2">
    <div class="col-6 col-md-3">
        <x-stat icon="bi-newspaper" label="کل مقالات" value="{{ fa_num($stats['all']) }}" color="primary"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-check2-circle" label="منتشرشده" value="{{ fa_num($stats['published']) }}" color="success"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-pencil" label="پیش‌نویس" value="{{ fa_num($stats['draft']) }}" color="warning"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-eye" label="مجموع بازدیدها" value="{{ fa_num($stats['views']) }}" color="info"/>
    </div>
</div>

<x-card title="فهرست مقالات" icon="bi-list-ul"
        actions="<a href='{{ route('admin.articles.create') }}' class='btn btn-sm btn-primary'><i class='bi bi-plus-lg'></i> مقاله جدید</a>">
    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-5">
            <input type="text" name="q" class="form-control form-control-sm" placeholder="جستجوی عنوان یا خلاصه..." value="{{ request('q') }}">
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select form-select-sm">
                <option value="">همه وضعیت‌ها</option>
                <option value="published" @if (request('status') === 'published') selected @endif>منتشرشده</option>
                <option value="draft" @if (request('status') === 'draft') selected @endif>پیش‌نویس</option>
            </select>
        </div>
        <div class="col-md-3">
            <select name="category_id" class="form-select form-select-sm">
                <option value="">همه دسته‌ها</option>
                @foreach ($categories as $id => $title)
                    <option value="{{ $id }}" @if (request('category_id') == $id) selected @endif>{{ $title }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-1 d-grid">
            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-funnel"></i></button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr><th>عنوان</th><th>دسته</th><th>نویسنده</th><th>وضعیت</th><th>عمومی</th><th>بازدید</th><th>انتشار</th><th></th></tr>
            </thead>
            <tbody>
            @forelse ($articles as $article)
                <tr>
                    <td class="small fw-bold">
                        {{ $article->title }}
                        <div class="text-muted text-truncate" style="max-width:220px" dir="ltr"><small>{{ $article->slug }}</small></div>
                    </td>
                    <td class="small">{{ $article->category?->title ?? '—' }}</td>
                    <td class="small">{{ $article->author?->name ?? '—' }}</td>
                    <td>
                        <x-badge :status="$article->status" :labels="['published'=>'منتشرشده','draft'=>'پیش‌نویس']"/>
                    </td>
                    <td>
                        @if ($article->is_public)
                            <span class="badge badge-soft-success">عمومی</span>
                        @else
                            <span class="badge badge-soft-secondary">خصوصی</span>
                        @endif
                    </td>
                    <td class="small">{{ fa_num($article->views) }}</td>
                    <td class="small text-muted">{{ $article->published_at ? fa_date($article->published_at) : '—' }}</td>
                    <td>
                        <div class="d-flex gap-1">
                            @if ($article->status === 'published' && $article->is_public)
                                <a href="{{ route('articles.public.show', $article) }}" target="_blank" class="btn btn-sm btn-outline-info" title="مشاهده در سایت"><i class="bi bi-eye"></i></a>
                            @endif
                            <a href="{{ route('admin.articles.edit', $article) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <form method="POST" action="{{ route('admin.articles.destroy', $article) }}"
                                  onsubmit="return confirm('حذف مقاله «{{ $article->title }}» مطمئن هستید؟')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8"><x-empty icon="bi-newspaper" title="مقاله‌ای ثبت نشده است" actionLabel="ایجاد مقاله" actionUrl="{{ route('admin.articles.create') }}"/></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</x-card>

{{ $articles->links() }}
@endsection
