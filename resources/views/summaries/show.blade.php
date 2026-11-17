@extends('layouts.app')

@section('title', $summary->title)
@section('page_title', 'بانک خلاصه و نکته')

@section('content')
@php $canManage = auth()->user()->canAccess('summaries.manage'); @endphp
<div class="row g-3">
    <div class="col-lg-8">
        <x-card>
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h5 class="fw-bold mb-1">{{ $summary->title }}</h5>
                    <div class="small text-muted">
                        @if ($summary->category)<span class="badge rounded-pill badge-soft-info">{{ $summary->category }}</span>@endif
                        @if ($summary->subject)
                            <span class="badge rounded-pill badge-soft-secondary">{{ $summary->subject->fullTitle() }}</span>
                        @endif
                        {{ $summary->user_id ? 'خلاصه شخصی' : 'خلاصه عمومی' }} — {{ fa_datetime($summary->created_at) }}
                    </div>
                </div>
                <div class="d-flex gap-1">
                    <form method="POST" action="{{ route('summaries.bookmark', $summary) }}">
                        @csrf
                        <button class="btn btn-sm {{ $bookmarked ? 'btn-warning' : 'btn-outline-warning' }}"
                                title="{{ $bookmarked ? 'حذف علامت' : 'علامت‌گذاری' }}">
                            <i class="bi {{ $bookmarked ? 'bi-bookmark-check-fill' : 'bi-bookmark' }}"></i>
                        </button>
                    </form>
                    @if ($summary->user_id === auth()->id() || $canManage)
                        <a href="{{ route('summaries.edit', $summary) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                        <form method="POST" action="{{ route('summaries.destroy', $summary) }}"
                              onsubmit="return confirm('این خلاصه حذف شود؟')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="summary-content" style="line-height:2">{{ $summary->content }}</div>

            @if ($summary->tags)
                <hr>
                @foreach ($summary->tags as $tag)
                    <span class="badge badge-soft-secondary ms-1">#{{ $tag }}</span>
                @endforeach
            @endif
        </x-card>

        {{-- یادداشت‌های من --}}
        <x-card title="یادداشت‌های من روی این خلاصه" icon="bi-journal-text">
            <form method="POST" action="{{ route('summaries.note.store', $summary) }}" class="mb-3">
                @csrf
                <x-textarea name="body" label="یادداشت جدید" rows="3" required placeholder="نکته یا پرسش خود را بنویسید..."/>
                <button class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> افزودن یادداشت</button>
            </form>
            @forelse ($notes as $note)
                <div class="border rounded p-2 mb-2 small">
                    <div class="text-muted mb-1">{{ fa_datetime($note->created_at) }}</div>
                    {{ $note->body }}
                </div>
            @empty
                <p class="text-muted small mb-0">هنوز یادداشتی ندارید.</p>
            @endforelse
        </x-card>
    </div>

    <div class="col-lg-4">
        <x-card title="مرور هوشمند" icon="bi-arrow-repeat">
            @if ($inReview)
                <p class="small text-muted">این خلاصه در صف مرور شما قرار دارد و طبق الگوریتم SM-2 بازمی‌گردد.</p>
                <a href="{{ route('review.index') }}" class="btn btn-outline-primary btn-sm w-100">مشاهده صف مرور</a>
            @else
                <p class="small text-muted mb-2">
                    با افزودن به مرور، این خلاصه طبق روش فاصله‌دار SM-2 در روزهای آینده به شما یادآوری می‌شود.
                </p>
                <form method="POST" action="{{ route('review.add') }}">
                    @csrf
                    <input type="hidden" name="type" value="summary">
                    <input type="hidden" name="id" value="{{ $summary->id }}">
                    <button class="btn btn-success w-100"><i class="bi bi-plus-circle"></i> افزودن به مرور</button>
                </form>
            @endif
        </x-card>

        <a href="{{ route('summaries.index') }}" class="btn btn-outline-secondary w-100"><i class="bi bi-arrow-right"></i> بازگشت به بانک خلاصه</a>
    </div>
</div>
@endsection
