@extends('layouts.app')

@section('title', 'بانک سؤال')
@section('page_title', 'بانک سؤال')

@section('content')
<x-alerts/>

<div class="row g-3 mb-2">
    <div class="col-6 col-md-3"><x-stat icon="bi-database" label="کل سؤالات" value="{{ fa_num($stats['all']) }}" color="primary"/></div>
    <div class="col-6 col-md-3"><x-stat icon="bi-ui-checks" label="تستی" value="{{ fa_num($stats['multi']) }}" color="success"/></div>
    <div class="col-6 col-md-3"><x-stat icon="bi-pencil" label="تشریحی" value="{{ fa_num($stats['descriptive']) }}" color="info"/></div>
    <div class="col-6 col-md-3"><x-stat icon="bi-eye-slash" label="غیرفعال" value="{{ fa_num($stats['inactive']) }}" color="warning"/></div>
</div>

<x-card title="فهرست سؤالات" icon="bi-database"
        actions="<a href='{{ route('admin.questions.create') }}' class='btn btn-sm btn-primary'><i class='bi bi-plus-lg'></i> سؤال جدید</a>">
    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-3">
            <input type="text" name="q" class="form-control form-control-sm" placeholder="جستجو در متن سؤال..." value="{{ request('q') }}">
        </div>
        <div class="col-md-3">
            <select name="subject_id" class="form-select form-select-sm">
                <option value="">همه دروس و مباحث</option>
                @foreach ($subjects as $id => $title)
                    <option value="{{ $id }}" @if (request('subject_id') == $id) selected @endif>{{ $title }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select name="type" class="form-select form-select-sm">
                <option value="">همه انواع</option>
                <option value="multi" @if (request('type') === 'multi') selected @endif>تستی</option>
                <option value="descriptive" @if (request('type') === 'descriptive') selected @endif>تشریحی</option>
            </select>
        </div>
        <div class="col-md-2">
            <select name="difficulty" class="form-select form-select-sm">
                <option value="">همه سطوح</option>
                <option value="easy" @if (request('difficulty') === 'easy') selected @endif>آسان</option>
                <option value="medium" @if (request('difficulty') === 'medium') selected @endif>متوسط</option>
                <option value="hard" @if (request('difficulty') === 'hard') selected @endif>دشوار</option>
            </select>
        </div>
        <div class="col-md-1">
            <select name="active" class="form-select form-select-sm">
                <option value="">وضعیت</option>
                <option value="1" @if (request('active') === '1') selected @endif>فعال</option>
                <option value="0" @if (request('active') === '0') selected @endif>غیرفعال</option>
            </select>
        </div>
        <div class="col-md-1 d-grid">
            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-funnel"></i></button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr><th style="width:60px">کد</th><th>متن سؤال</th><th>درس / مبحث</th><th>نوع</th><th>سختی</th><th>بارم</th><th>وضعیت</th><th style="width:110px"></th></tr>
            </thead>
            <tbody>
            @forelse ($questions as $question)
                <tr>
                    <td class="small text-muted">{{ fa_num($question->id) }}</td>
                    <td>
                        <a href="{{ route('admin.questions.edit', $question) }}" class="fw-bold text-decoration-none" title="ویرایش">
                            {{ \Illuminate\Support\Str::limit(strip_tags($question->text), 80) }}
                        </a>
                        @if ($question->explanation)
                            <div class="text-muted small text-truncate" style="max-width:320px"><i class="bi bi-lightbulb small"></i> {{ \Illuminate\Support\Str::limit(strip_tags($question->explanation), 60) }}</div>
                        @endif
                    </td>
                    <td class="small">
                        @if ($question->subject?->parent)
                            <span class="text-muted">{{ $question->subject->parent->title }}</span> ›
                        @endif
                        {{ $question->subject?->title ?? '—' }}
                    </td>
                    <td>
                        @if ($question->type === 'multi')
                            <span class="badge badge-soft-success">تستی</span>
                        @else
                            <span class="badge badge-soft-info">تشریحی</span>
                        @endif
                    </td>
                    <td class="small">
                        @if ($question->difficulty === 'easy') <span class="text-success">آسان</span>
                        @elseif ($question->difficulty === 'hard') <span class="text-danger">دشوار</span>
                        @else <span class="text-warning">متوسط</span> @endif
                    </td>
                    <td class="small">{{ fa_num($question->default_marks) }}</td>
                    <td>
                        @if ($question->is_active)
                            <span class="badge badge-soft-success">فعال</span>
                        @else
                            <span class="badge badge-soft-secondary">غیرفعال</span>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <form method="POST" action="{{ route('admin.questions.toggle', $question) }}" title="{{ $question->is_active ? 'غیرفعال‌سازی' : 'فعال‌سازی' }}">
                                @csrf
                                <button class="btn btn-sm {{ $question->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}"><i class="bi {{ $question->is_active ? 'bi-pause' : 'bi-play' }}"></i></button>
                            </form>
                            <a href="{{ route('admin.questions.edit', $question) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <form method="POST" action="{{ route('admin.questions.destroy', $question) }}"
                                  onsubmit="return confirm('حذف سؤال؛ در آزمون‌های موجود نیز حذف خواهد شد. مطمئن هستید؟')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8"><x-empty icon="bi-database" title="سؤالی یافت نشد" actionLabel="ثبت سؤال جدید" actionUrl="{{ route('admin.questions.create') }}"/></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</x-card>

{{ $questions->links() }}
@endsection
