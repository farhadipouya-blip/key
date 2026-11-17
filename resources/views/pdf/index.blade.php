@extends('layouts.app')

@section('title', 'مطالعه PDF')
@section('page_title', 'مطالعه PDF')

@section('content')
<div class="row g-3 mb-2">
    <div class="col-6 col-md-4">
        <x-stat icon="bi-file-earmark-pdf" label="فایل‌های قابل مطالعه" value="{{ fa_num($media->count()) }}" color="primary"/>
    </div>
    <div class="col-6 col-md-4">
        <x-stat icon="bi-hourglass-half" label="در حال مطالعه (شروع‌شده)" value="{{ fa_num($media->filter(fn ($m) => $m->read && ($m->read->progress ?? 0) < 100)->count()) }}" color="warning"/>
    </div>
    <div class="col-6 col-md-4">
        <x-stat icon="bi-check2-circle" label="کامل‌شده" value="{{ fa_num($media->filter(fn ($m) => ($m->read->progress ?? 0) >= 100)->count()) }}" color="success"/>
    </div>
</div>

<div class="row g-3">
    @forelse ($media as $item)
        <div class="col-md-6 col-lg-4">
            <x-card>
                <div class="d-flex align-items-start gap-3">
                    <div class="stat-icon" style="background: rgba(220,38,38,.1); color: #dc2626; width:52px; height:52px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.5rem; flex-shrink:0">
                        <i class="bi bi-file-earmark-pdf"></i>
                    </div>
                    <div class="flex-grow-1 min-width-0">
                        <div class="fw-bold text-truncate" title="{{ $item->name }}">{{ $item->name }}</div>
                        <div class="small text-muted mb-1">{{ $item->humanSize() }} — {{ fa_num($item->highlights_count) }} هایلایت</div>
                        <span class="badge rounded-pill badge-soft-{{ $item->source_badge }}">{{ $item->source }}</span>
                    </div>
                </div>

                <div class="mt-3">
                    @if ($item->read)
                        <div class="d-flex justify-content-between small mb-1">
                            <span>پیشرفت مطالعه: <span class="fw-bold">{{ fa_num($item->read->progress) }}٪</span></span>
                            <span class="text-muted">صفحه {{ fa_num($item->read->current_page) }} از {{ $item->read->total_pages ? fa_num($item->read->total_pages) : '؟' }}</span>
                        </div>
                        <div class="progress mb-2" style="height:8px">
                            <div class="progress-bar {{ ($item->read->progress ?? 0) >= 100 ? 'bg-success' : 'bg-primary' }}" style="width: {{ max(2, $item->read->progress) }}%"></div>
                        </div>
                        <div class="small text-muted mb-2">آخرین مطالعه: {{ fa_datetime($item->read->last_read_at) }}</div>
                    @else
                        <div class="small text-muted mb-2">هنوز مطالعه را شروع نکرده‌اید</div>
                    @endif
                    <a href="{{ route('pdf.read', $item) }}" class="btn btn-sm btn-primary w-100">
                        <i class="bi bi-book ms-1"></i> {{ $item->read ? 'ادامه مطالعه' : 'شروع مطالعه' }}
                    </a>
                </div>
            </x-card>
        </div>
    @empty
        <div class="col-12">
            <x-card>
                <x-empty icon="bi-file-earmark-pdf" title="فایلی برای مطالعه موجود نیست"
                         message="PDFهای عمومی کتابخانه، فایل‌های درس‌های دوره‌های ثبت‌نام‌شده شما و فایل‌های شخصی‌تان اینجا نمایش داده می‌شوند. هنوز هیچ PDFی با این شرایط وجود ندارد."/>
            </x-card>
        </div>
    @endforelse
</div>
@endsection
