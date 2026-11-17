@extends('layouts.app')

@section('title', 'بانک خلاصه و نکته')
@section('page_title', 'بانک خلاصه و نکته')

@section('content')
<div class="row g-3 mb-3">
    <div class="col-6 col-md-4">
        <x-stat icon="bi-globe2" label="خلاصه‌های عمومی" value="{{ fa_num($counts['public']) }}" color="primary" url="{{ route('summaries.index', ['tab' => 'public']) }}"/>
    </div>
    <div class="col-6 col-md-4">
        <x-stat icon="bi-person-lines-fill" label="خلاصه‌های شخصی من" value="{{ fa_num($counts['mine']) }}" color="info" url="{{ route('summaries.index', ['tab' => 'mine']) }}"/>
    </div>
    <div class="col-6 col-md-4">
        <x-stat icon="bi-bookmarks" label="علامت‌گذاری‌شده‌ها" value="{{ fa_num($counts['bookmarked']) }}" color="warning" url="{{ route('summaries.index', ['tab' => 'bookmarked']) }}"/>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <ul class="nav nav-pills">
        @foreach (['public' => 'عمومی', 'mine' => 'شخصی من', 'bookmarked' => 'علامت‌گذاری‌شده'] as $key => $label)
            <li class="nav-item">
                <a class="nav-link {{ $tab === $key ? 'active' : '' }}"
                   href="{{ route('summaries.index', ['tab' => $key]) }}">{{ $label }}</a>
            </li>
        @endforeach
    </ul>
    <a href="{{ route('summaries.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> خلاصه جدید</a>
</div>

{{-- فیلترها --}}
<x-card bodyClass="py-3">
    <form method="GET" action="{{ route('summaries.index') }}" class="row g-2 align-items-end">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <div class="col-md-4">
            <label class="form-label small">جست‌وجو</label>
            <input type="text" name="q" value="{{ $q }}" class="form-control form-control-sm" placeholder="عنوان یا متن خلاصه...">
        </div>
        <div class="col-md-3">
            <label class="form-label small">دسته</label>
            <select name="category" class="form-select form-select-sm">
                <option value="">همه دسته‌ها</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat }}" @if($category === $cat) selected @endif>{{ $cat }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small">درس</label>
            <select name="subject_id" class="form-select form-select-sm">
                <option value="">همه دروس</option>
                @foreach ($subjects as $s)
                    <option value="{{ $s->id }}" @if((string) $subjectId === (string) $s->id) selected @endif>{{ $s->fullTitle() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <button class="btn btn-outline-primary btn-sm w-100"><i class="bi bi-search"></i> فیلتر</button>
        </div>
    </form>
</x-card>

<div class="row g-3">
    @forelse ($summaries as $summary)
        <div class="col-md-6 col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h6 class="fw-bold mb-0">{{ $summary->title }}</h6>
                        @if ($summary->category)
                            <span class="badge rounded-pill badge-soft-info">{{ $summary->category }}</span>
                        @endif
                    </div>
                    @if ($summary->subject)
                        <span class="badge badge-soft-secondary mb-2">{{ $summary->subject->fullTitle() }}</span>
                    @endif
                    <p class="small text-muted mb-3">{{ \Illuminate\Support\Str::limit(strip_tags($summary->content), 110) }}</p>
                    @if ($summary->tags)
                        <div class="mb-2">
                            @foreach ($summary->tags as $tag)
                                <span class="badge badge-soft-secondary ms-1">#{{ $tag }}</span>
                            @endforeach
                        </div>
                    @endif
                    <div class="d-flex justify-content-between align-items-center small text-muted">
                        <span>{{ $summary->user_id ? 'شخصی' : 'عمومی' }} — {{ fa_date($summary->created_at) }}</span>
                    </div>
                    <a href="{{ route('summaries.show', $summary) }}" class="btn btn-outline-primary btn-sm w-100 mt-2">مشاهده</a>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <x-empty icon="bi-collection" title="خلاصه‌ای یافت نشد"
                     message="در این تب خلاصه‌ای وجود ندارد یا فیلترها نتیجه‌ای نداشتند."
                     actionLabel="ساخت خلاصه جدید" actionUrl="{{ route('summaries.create') }}"/>
        </div>
    @endforelse
</div>

<div class="mt-3">{{ $summaries->links() }}</div>
@endsection
