@extends('layouts.app')

@section('title', 'کتابخانه رسانه')
@section('page_title', 'کتابخانه رسانه')

@php
    $typeIcons = [
        'image' => 'bi-file-earmark-image text-success',
        'video' => 'bi-file-earmark-play text-danger',
        'audio' => 'bi-file-earmark-music text-info',
        'pdf' => 'bi-file-earmark-pdf text-danger',
        'document' => 'bi-file-earmark-word text-primary',
        'archive' => 'bi-file-earmark-zip text-warning',
        'other' => 'bi-file-earmark text-secondary',
    ];
    $currentFolder = $folderId !== null && $folderId !== '' ? (int) $folderId : null;
@endphp

@section('content')
<div class="row g-3">
    {{-- ستون پوشه‌ها --}}
    <div class="col-lg-3">
        <x-card title="پوشه‌ها" icon="bi-folder2" bodyClass="py-2">
            <div class="list-group list-group-flush mb-3">
                <a href="{{ route('media.index') }}"
                   class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ $folderId === null || $folderId === '' ? 'active' : '' }}">
                    <span><i class="bi bi-collection"></i> همه فایل‌ها</span>
                    <span class="badge rounded-pill {{ $folderId === null || $folderId === '' ? 'bg-light text-dark' : 'badge-soft-secondary' }}">{{ fa_num($counts['all']) }}</span>
                </a>
                <a href="{{ route('media.index', ['folder_id' => 0]) }}"
                   class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ $folderId === '0' ? 'active' : '' }}">
                    <span><i class="bi bi-folder-minus"></i> بدون پوشه</span>
                    <span class="badge rounded-pill badge-soft-secondary">{{ fa_num($counts['no_folder']) }}</span>
                </a>
                @forelse ($folders as $folder)
                    <a href="{{ route('media.index', ['folder_id' => $folder->id]) }}"
                       class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ $currentFolder === $folder->id ? 'active' : '' }}">
                        <span class="text-truncate"><i class="bi bi-folder2"></i> {{ $folder->name }}</span>
                        <span class="badge rounded-pill {{ $currentFolder === $folder->id ? 'bg-light text-dark' : 'badge-soft-secondary' }}">{{ fa_num($folder->files_count) }}</span>
                    </a>
                @empty
                    <div class="text-center text-muted small py-2">پوشه‌ای ساخته نشده است</div>
                @endforelse
            </div>

            <form method="POST" action="{{ route('media.folders.store') }}">
                @csrf
                <label class="form-label small">پوشه جدید</label>
                <div class="input-group input-group-sm mb-2">
                    <input type="text" name="name" class="form-control" placeholder="نام پوشه..." required maxlength="120">
                    <button class="btn btn-primary"><i class="bi bi-plus-lg"></i></button>
                </div>
                @error('name') <div class="small text-danger">{{ $message }}</div> @enderror
            </form>
        </x-card>

        <x-card title="فیلتر نوع فایل" icon="bi-funnel">
            <form method="GET" action="{{ route('media.index') }}">
                @if ($folderId !== null && $folderId !== '')
                    <input type="hidden" name="folder_id" value="{{ $folderId }}">
                @endif
                <input type="hidden" name="q" value="{{ $q }}">
                <label class="form-label small">جست‌وجوی نام فایل</label>
                <input type="text" name="q" value="{{ $q }}" class="form-control form-control-sm mb-3" placeholder="نام فایل...">
                <label class="form-label small">گروه فایل</label>
                <select name="type" class="form-select form-select-sm mb-3">
                    <option value="">همه انواع</option>
                    @foreach ($typeOptions as $key => $label)
                        <option value="{{ $key }}" @if ($type === $key) selected @endif>{{ $label }}</option>
                    @endforeach
                </select>
                <button class="btn btn-sm btn-outline-primary w-100"><i class="bi bi-search"></i> اعمال فیلتر</button>
            </form>
        </x-card>
    </div>

    {{-- ستون فایل‌ها --}}
    <div class="col-lg-9">
        <x-card title="بارگذاری فایل (چندفایلی)" icon="bi-cloud-arrow-up">
            <form method="POST" action="{{ route('media.upload') }}" enctype="multipart/form-data">
                @csrf
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-6">
                        <label class="form-label small">انتخاب فایل‌ها <span class="text-danger">*</span></label>
                        <input type="file" name="files[]" multiple required class="form-control">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small">مقصد</label>
                        <select name="folder_id" class="form-select">
                            <option value="">بدون پوشه</option>
                            @foreach ($folders as $folder)
                                <option value="{{ $folder->id }}" @if ($currentFolder === $folder->id) selected @endif>{{ $folder->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small">دسترسی</label>
                        <select name="access" class="form-select">
                            <option value="private">خصوصی</option>
                            <option value="public">عمومی</option>
                            <option value="staff">کارکنان</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-1 d-grid">
                        <button class="btn btn-primary"><i class="bi bi-upload"></i></button>
                    </div>
                </div>
                <div class="form-text mt-2">
                    حداکثر ۲۰ مگابایت برای هر فایل — پسوندهای مجاز: jpg، jpeg، png، webp، gif، pdf، mp4، mp3، zip، doc، docx، xls، xlsx، csv، txt — فایل‌های اجرایی (php، exe، js، html و...) مسدود می‌شوند.
                </div>
            </form>
        </x-card>

        <x-card bodyClass="py-0">
            <div class="d-flex flex-wrap justify-content-between align-items-center px-3 py-2 border-bottom gap-2">
                <div class="d-flex align-items-center gap-3">
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="checkbox" id="checkAll" title="انتخاب همه">
                        <label class="form-check-label small" for="checkAll">انتخاب همه</label>
                    </div>
                    <span class="small text-muted">{{ fa_num($files->total()) }} فایل</span>
                </div>
                <form method="POST" action="{{ route('media.delete-bulk') }}" id="bulkDeleteForm" class="d-flex gap-2"
                      onsubmit="return confirm('فایل‌های انتخاب‌شده حذف شوند؟ این عمل بازگشت‌پذیر نیست.')">
                    @csrf
                    <input type="hidden" name="ids" id="bulkIds">
                    <button class="btn btn-sm btn-outline-danger" id="bulkDeleteBtn" disabled>
                        <i class="bi bi-trash"></i> حذف گروهی
                    </button>
                </form>
            </div>

            @if ($files->isNotEmpty())
                <div class="row g-3 p-3">
                    @foreach ($files as $m)
                        <div class="col-6 col-md-4 col-xl-3">
                            <div class="border rounded-3 p-2 h-100 d-flex flex-column media-card">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <input class="form-check-input file-check" type="checkbox" value="{{ $m->id }}"
                                           style="width:1.1rem;height:1.1rem" title="انتخاب برای حذف گروهی">
                                    @if ($m->type === 'image' && storage_public_url($m->path))
                                        <a href="{{ storage_public_url($m->path) }}" target="_blank" class="text-decoration-none">
                                            <img src="{{ storage_public_url($m->path) }}" alt="{{ $m->name }}"
                                                 class="rounded-2 border" style="height:64px;width:64px;object-fit:cover">
                                        </a>
                                    @else
                                        <i class="bi {{ $typeIcons[$m->type] ?? $typeIcons['other'] }}" style="font-size:2.4rem"></i>
                                    @endif
                                    <x-badge :status="$m->access" :labels="['public' => 'عمومی', 'private' => 'خصوصی', 'staff' => 'کارکنان']"/>
                                </div>
                                <div class="small fw-bold text-truncate mb-1" title="{{ $m->name }}">{{ $m->name }}</div>
                                <div class="text-muted" style="font-size:.72rem" title="{{ $m->file_name }}">{{ $m->file_name }}</div>
                                <div class="text-muted mt-auto" style="font-size:.75rem">
                                    <div>{{ $m->humanSize() }} — {{ fa_date($m->created_at) }}</div>
                                    <div class="d-flex justify-content-between">
                                        <span>{{ $m->folder?->name ?: 'بدون پوشه' }}</span>
                                        <span title="بارگذار">{{ $m->uploader?->name ?? '—' }}</span>
                                    </div>
                                </div>
                                <div class="d-flex gap-1 mt-2">
                                    <a href="{{ route('media.download', $m) }}" class="btn btn-sm btn-outline-primary flex-grow-1" title="دانلود">
                                        <i class="bi bi-download"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#renameModal"
                                            data-id="{{ $m->id }}" data-name="{{ $m->name }}" title="تغییر نام">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#moveModal"
                                            data-id="{{ $m->id }}" data-folder="{{ $m->folder_id }}" title="انتقال">
                                        <i class="bi bi-folder-symlink"></i>
                                    </button>
                                    <form method="POST" action="{{ route('media.destroy', $m) }}" class="d-inline"
                                          onsubmit="return confirm('فایل «{{ $m->name }}» حذف شود؟')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" title="حذف"><i class="bi bi-trash"></i></button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <x-empty icon="bi-collection" title="فایلی یافت نشد"
                         message="با فرم بالا فایل بارگذاری کنید یا فیلترها را تغییر دهید."/>
            @endif
        </x-card>
        {{ $files->links() }}
    </div>
</div>

{{-- مودال تغییر نام --}}
<div class="modal fade" id="renameModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" id="renameForm">
            @csrf
            <div class="modal-header">
                <h6 class="modal-title"><i class="bi bi-pencil"></i> تغییر نام فایل</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="text" class="form-control" name="name" id="renameInput" required maxlength="200">
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal" type="button">انصراف</button>
                <button class="btn btn-primary btn-sm" type="submit">ذخیره</button>
            </div>
        </form>
    </div>
</div>

{{-- مودال انتقال به پوشه --}}
<div class="modal fade" id="moveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" id="moveForm">
            @csrf
            <div class="modal-header">
                <h6 class="modal-title"><i class="bi bi-folder-symlink"></i> انتقال فایل به پوشه</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <select class="form-select" name="folder_id" id="moveFolderSelect">
                    <option value="">بدون پوشه</option>
                    @foreach ($folders as $folder)
                        <option value="{{ $folder->id }}">{{ $folder->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal" type="button">انصراف</button>
                <button class="btn btn-primary btn-sm" type="submit">انتقال</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const checks = () => Array.from(document.querySelectorAll('.file-check'));

    document.getElementById('checkAll').addEventListener('change', function () {
        checks().forEach(cb => { cb.checked = this.checked; });
        refreshBulk();
    });

    function refreshBulk() {
        const selected = checks().filter(cb => cb.checked);
        const btn = document.getElementById('bulkDeleteBtn');
        btn.disabled = selected.length === 0;
        if (selected.length > 0) {
            btn.innerHTML = '<i class="bi bi-trash"></i> حذف ' + selected.length.toLocaleString('fa-IR') + ' فایل';
            document.getElementById('bulkIds').value = selected.map(cb => cb.id || cb.value).join(',');
        } else {
            document.getElementById('bulkIds').value = '';
        }
    }

    document.querySelectorAll('.file-check').forEach(cb => cb.addEventListener('change', refreshBulk));

    // حذف گروهی: ids به‌صورت آرایه ارسال شود
    document.getElementById('bulkDeleteForm').addEventListener('submit', function () {
        const values = checks().filter(cb => cb.checked).map(cb => cb.value);
        this.querySelectorAll('input[name="ids[]"], input[name="ids"]').forEach(i => i.remove());
        values.forEach(v => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = v;
            this.appendChild(input);
        });
    });

    // مودال تغییر نام
    const renameModal = document.getElementById('renameModal');
    renameModal.addEventListener('show.bs.modal', function (event) {
        const btn = event.relatedTarget;
        if (!btn) return;
        document.getElementById('renameInput').value = btn.getAttribute('data-name');
        document.getElementById('renameForm').action = '{{ url('media') }}/' + btn.getAttribute('data-id') + '/rename';
    });

    // مودال انتقال
    const moveModal = document.getElementById('moveModal');
    moveModal.addEventListener('show.bs.modal', function (event) {
        const btn = event.relatedTarget;
        if (!btn) return;
        const folder = btn.getAttribute('data-folder');
        const select = document.getElementById('moveFolderSelect');
        select.value = folder && folder !== '' ? folder : '';
        document.getElementById('moveForm').action = '{{ url('media') }}/' + btn.getAttribute('data-id') + '/move';
    });
})();
</script>
@endpush
