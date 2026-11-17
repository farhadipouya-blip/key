@extends('layouts.public')

@section('title', $article->meta_title ?: $article->title)

@section('content')
<div class="row g-4">
    <div class="col-lg-8">
        <x-card bodyClass="p-4">
            <nav aria-label="breadcrumb" class="small mb-2">
                <a href="{{ route('articles.public.index') }}" class="text-decoration-none">مقالات</a>
                @if ($article->category)
                    <span class="text-muted">/ {{ $article->category->title }}</span>
                @endif
            </nav>
            <h1 class="fw-bold mb-2" style="font-size:1.6rem">{{ $article->title }}</h1>
            <div class="d-flex flex-wrap gap-3 small text-muted border-bottom pb-3 mb-3">
                @if ($article->author)
                    <span><i class="bi bi-person"></i> {{ $article->author->name }}</span>
                @endif
                @if ($article->published_at)
                    <span><i class="bi bi-calendar3"></i> {{ fa_date($article->published_at) }}</span>
                @endif
                <span><i class="bi bi-eye"></i> {{ fa_num($article->views + 1) }} بازدید</span>
            </div>

            @if ($article->cover_path)
                <img src="{{ storage_public_url($article->cover_path) }}" alt="{{ $article->title }}" class="img-fluid rounded mb-3" style="max-height:380px;width:100%;object-fit:cover">
            @endif

            @if ($article->excerpt)
                <p class="bg-light rounded p-3 small text-muted fst-italic">{{ $article->excerpt }}</p>
            @endif

            <div class="article-body lh-lg">
                {!! nl2br(e($article->content)) !!}
            </div>
        </x-card>

        {{-- دیدگاه‌ها --}}
        <x-card title="دیدگاه‌ها ({{ fa_num($comments->count()) }})" icon="bi-chat-dots">
            @forelse ($comments as $comment)
                <div class="d-flex gap-2 border-bottom pb-2 mb-2 {{ $loop->last ? 'border-bottom-0 mb-0 pb-0' : '' }}">
                    <span class="avatar bg-primary-subtle text-primary rounded-circle d-grid" style="width:38px;height:38px;place-items:center;font-weight:700">{{ mb_substr($comment->user?->name ?? '؟', 0, 1) }}</span>
                    <div>
                        <div class="small fw-bold">{{ $comment->user?->name ?? 'کاربر' }}
                            <span class="text-muted small fw-normal">— {{ fa_datetime($comment->created_at) }}</span>
                        </div>
                        <div class="small lh-lg">{{ $comment->body }}</div>
                    </div>
                </div>
            @empty
                <p class="text-muted small mb-0">هنوز دیدگاهی ثبت نشده است؛ اولین نفر باشید!</p>
            @endforelse

            @if ($pendingCount > 0)
                <div class="alert alert-warning small mt-3 mb-0">
                    <i class="bi bi-hourglass-split"></i> {{ fa_num($pendingCount) }} دیدگاه شما در انتظار تأیید مدیر است و پس از تأیید نمایش داده می‌شود.
                </div>
            @endif
        </x-card>

        <x-card title="ثبت دیدگاه جدید" icon="bi-pencil-square">
            @auth
                <form method="POST" action="{{ route('articles.public.comment', $article) }}">
                    @csrf
                    <div class="mb-2 small text-muted">
                        <i class="bi bi-person-check"></i> ارسال‌کننده: <strong>{{ auth()->user()->name }}</strong>
                    </div>
                    <x-textarea name="body" rows="4" required placeholder="دیدگاه خود را بنویسید..." :value="old('body')"/>
                    <div class="form-text mb-2">دیدگاه شما پس از تأیید مدیر منتشر می‌شود.</div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-send"></i> ارسال دیدگاه</button>
                </form>
            @else
                <div class="alert alert-info d-flex gap-2 align-items-center">
                    <i class="bi bi-info-circle"></i>
                    <div>برای ثبت دیدگاه باید وارد حساب کاربری خود شوید.
                        <a href="{{ route('login') }}" class="alert-link">ورود به حساب</a>
                    </div>
                </div>
            @endauth
        </x-card>
    </div>

    <div class="col-lg-4">
        <x-card title="مقالات مرتبط" icon="bi-journal-text">
            @forelse ($related as $rel)
                <a href="{{ route('articles.public.show', $rel) }}" class="d-block text-decoration-none text-body border-bottom py-2 {{ $loop->last ? 'border-bottom-0' : '' }}">
                    <div class="small fw-bold">{{ $rel->title }}</div>
                    <div class="small text-muted"><i class="bi bi-eye"></i> {{ fa_num($rel->views) }} بازدید</div>
                </a>
            @empty
                <p class="text-muted small mb-0">مقاله مرتبطی یافت نشد.</p>
            @endforelse
        </x-card>

        <x-card title="دسترسی سریع" icon="bi-link-45deg">
            <a href="{{ route('articles.public.index') }}" class="btn btn-outline-primary btn-sm w-100 mb-2"><i class="bi bi-list-ul"></i> همه مقالات</a>
            <a href="{{ route('verify.certificate.form') }}" class="btn btn-outline-secondary btn-sm w-100"><i class="bi bi-shield-check"></i> استعلام گواهی‌نامه</a>
        </x-card>
    </div>
</div>
@endsection
