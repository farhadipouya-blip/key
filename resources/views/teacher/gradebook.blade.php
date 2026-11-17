@extends('layouts.app')

@section('title', 'دفتر نمره')
@section('page_title', 'دفتر نمره — دانشجویان و نمرات')

@section('content')
@forelse ($gradebooks as $book)
    <x-card title="{{ $book['course']->title }}" icon="bi-table"
            bodyClass="py-2"
            actions="<span class='badge badge-soft-secondary'>{{ fa_num($book['course']->lessons_count) }} درس</span>">
        @if ($book['rows']->isEmpty())
            <div class="text-muted small py-2"><i class="bi bi-inbox"></i> دانشجویی در این دوره ثبت‌نام نکرده است.</div>
        @else
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>دانشجو</th>
                            <th>دروس تکمیل‌شده</th>
                            <th>پیشرفت دوره</th>
                            <th>میانگین نمره تکالیف</th>
                            <th>تکالیف تصحیح‌شده</th>
                            <th>منبع ثبت‌نام</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($book['rows'] as $row)
                        <tr>
                            <td class="small fw-bold">{{ $row['user']?->name }}</td>
                            <td class="small">{{ fa_num($row['done_lessons']) }} / {{ fa_num($row['lesson_count']) }}</td>
                            <td style="min-width:130px">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height:6px">
                                        <div class="progress-bar {{ $row['progress'] >= 100 ? 'bg-success' : 'bg-primary' }}" style="width: {{ $row['progress'] }}%"></div>
                                    </div>
                                    <span class="small">{{ fa_num($row['progress']) }}٪</span>
                                </div>
                            </td>
                            <td class="small fw-bold {{ ($row['avg_score'] ?? 0) < 50 ? 'text-danger' : 'text-success' }}">
                                {{ $row['avg_score'] !== null ? fa_num(round((float) $row['avg_score'], 1)) : '—' }}
                            </td>
                            <td class="small">{{ fa_num($row['graded_count']) }}</td>
                            <td class="small">
                                <x-badge :status="$row['enrollment']->source"
                                         :labels="['purchase'=>'خرید دوره','free'=>'ثبت‌نام رایگان','manual'=>'دستی']"/>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>
@empty
    <x-empty icon="bi-table" title="دوره‌ای برای نمایش دفتر نمره وجود ندارد"
             message="دوره‌های اختصاص‌یافته به شما اینجا نمایش داده می‌شوند."
             actionLabel="دوره‌های من" actionUrl="{{ route('teacher.courses') }}"/>
@endforelse
@endsection
