@extends('layouts.app')

@section('title', 'چت آنلاین')
@section('page_title', 'چت آنلاین')

@php
    $typeIcons = ['private' => 'bi-person', 'group' => 'bi-people', 'class' => 'bi-mortarboard'];
    $typeLabels = ['private' => 'خصوصی', 'group' => 'گروهی', 'class' => 'کلاسی'];
    $meId = auth()->id();
@endphp

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h5 class="fw-bold mb-1">گفتگوها</h5>
        <span class="text-muted small">گفتگوی خصوصی، گروهی و کلاسی با امکان نظرسنجی و وایت‌برد</span>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#groupModal">
            <i class="bi bi-people"></i> گروه جدید
        </button>
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#privateModal">
            <i class="bi bi-person-plus"></i> گفتگوی خصوصی
        </button>
    </div>
</div>

{{-- تب‌ها --}}
<ul class="nav nav-pills mb-3" id="roomTabs">
    <li class="nav-item"><button class="nav-link active" data-filter="all">همه</button></li>
    <li class="nav-item"><button class="nav-link" data-filter="private">خصوصی</button></li>
    <li class="nav-item"><button class="nav-link" data-filter="group">گروهی</button></li>
    <li class="nav-item"><button class="nav-link" data-filter="class">کلاسی</button></li>
</ul>

<div class="row g-3" id="roomsGrid">
    @forelse ($rooms as $room)
        @php
            $last = $room->messages->first();
            $preview = match ($last?->type) {
                'image' => '📷 تصویر',
                'audio' => '🎤 پیام صوتی',
                'file' => '📎 فایل پیوست',
                'poll' => '📊 نظرسنجی: ' . ($last->body ?: ''),
                'system' => str_starts_with((string) $last->body, '{"wb"') ? '✏️ وایت‌برد به‌روزرسانی شد' : '🔔 ' . ($last->body ?: ''),
                default => $last?->body,
            };
            $title = $room->type === 'private'
                ? ($room->users->first()?->name ?? 'گفتگو')
                : ($room->name ?: ($typeLabels[$room->type] ?? 'گفتگو'));
            $lastRead = (int) ($room->pivot->last_read_message_id ?? 0);
            $hasUnread = $last && $last->id > $lastRead && (int) $last->user_id !== $meId;
        @endphp
        <div class="col-md-6 col-xl-4 room-card" data-type="{{ $room->type }}">
            <a href="{{ route('chat.room', $room) }}" class="text-decoration-none">
                <div class="card h-100 room-tile">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="room-icon"><i class="bi {{ $typeIcons[$room->type] ?? 'bi-chat' }}"></i></span>
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-bold text-body text-truncate">{{ $title }}</div>
                                <span class="badge badge-soft-secondary">{{ $typeLabels[$room->type] ?? $room->type }}</span>
                            </div>
                            @if ($hasUnread)
                                <span class="badge rounded-pill bg-danger unread-dot" title="پیام جدید">جدید</span>
                            @endif
                        </div>
                        @if ($last)
                            <p class="small text-muted mb-1 text-truncate">{{ \Illuminate\Support\Str::limit($preview, 80) }}</p>
                            <div class="d-flex justify-content-between small text-muted">
                                <span><i class="bi bi-clock-history"></i> {{ fa_datetime($room->last_message_at ?: $last->created_at) }}</span>
                                <span><i class="bi bi-people"></i> {{ fa_num($room->users->count() + 1) }} عضو</span>
                            </div>
                        @else
                            <p class="small text-muted mb-0">هنوز پیامی رد و بدل نشده است — شروع به گفتگو کنید.</p>
                        @endif
                    </div>
                </div>
            </a>
        </div>
    @empty
        <div class="col-12">
            <x-empty icon="bi-chat-dots" title="هنوز گفتگویی ندارید"
                     message="یک گفتگوی خصوصی با پشتیبانی یا مشاور شروع کنید یا گروهی جدید بسازید."
                     actionLabel="گفتگوی خصوصی جدید" actionUrl="#privateModal" />
        </div>
    @endforelse
</div>

{{-- مودال ساخت گروه --}}
<div class="modal fade" id="groupModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('chat.group.store') }}">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title fw-bold"><i class="bi bi-people text-primary"></i> ساخت گروه جدید</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="fld-group-name">نام گروه <span class="text-danger">*</span></label>
                        <input type="text" id="fld-group-name" name="name" class="form-control" required maxlength="120" placeholder="مثلاً: گروه پروژه ریاضی">
                    </div>
                    <label class="form-label">انتخاب اعضا</label>
                    <div class="member-pick border rounded p-2">
                        @forelse ($contacts as $contact)
                            <label class="d-flex align-items-center gap-2 py-1 px-2 rounded member-pick-item">
                                <input type="checkbox" name="user_ids[]" value="{{ $contact->id }}" class="form-check-input mt-0">
                                <span>{{ $contact->name }}</span>
                            </label>
                        @empty
                            <div class="small text-muted p-2">کاربری برای افزودن به گروه وجود ندارد.</div>
                        @endforelse
                    </div>
                    <div class="form-text">اگر عضوی انتخاب نکنید، گروه فقط شامل خود شما خواهد بود.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-plus-lg"></i> ساخت گروه</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- مودال گفتگوی خصوصی --}}
<div class="modal fade" id="privateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <form method="POST" action="{{ route('chat.private.store') }}">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title fw-bold"><i class="bi bi-person-plus text-primary"></i> شروع گفتگوی خصوصی</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label" for="fld-private-user">کارمند یا مشاور</label>
                    <select id="fld-private-user" name="user_id" class="form-select" required>
                        <option value="">انتخاب کنید</option>
                        @foreach ($staff as $member)
                            <option value="{{ $member->id }}">{{ $member->name }}</option>
                        @endforeach
                    </select>
                    @if ($staff->isEmpty())
                        <div class="small text-muted mt-2">در حال حاضر کارمندی فعال برای گفتگو وجود ندارد.</div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-chat-dots"></i> شروع گفتگو</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .room-tile { transition: box-shadow .15s ease, transform .15s ease; }
    .room-tile:hover { box-shadow: 0 .5rem 1.2rem rgba(47, 91, 234, .12); transform: translateY(-2px); }
    .room-icon { width: 2.5rem; height: 2.5rem; border-radius: 12px; background: rgba(47, 91, 234, .1); color: var(--key-primary, #2f5bea); display: inline-flex; align-items: center; justify-content: center; font-size: 1.15rem; flex-shrink: 0; }
    .min-w-0 { min-width: 0; }
    .member-pick { max-height: 220px; overflow-y: auto; }
    .member-pick-item:hover { background: rgba(47, 91, 234, .05); }
    .unread-dot { animation: pulse 1.4s infinite; }
    @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: .55; } }
</style>
@endpush

@push('scripts')
<script>
    // فیلتر تب‌های اتاق‌ها
    document.querySelectorAll('#roomTabs [data-filter]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('#roomTabs .nav-link').forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');
            const f = btn.getAttribute('data-filter');
            document.querySelectorAll('.room-card').forEach(function (card) {
                card.style.display = (f === 'all' || card.getAttribute('data-type') === f) ? '' : 'none';
            });
        });
    });

    // باز کردن مودال گفتگوی خصوصی از دکمه حالت خالی
    document.querySelectorAll('a[href="#privateModal"]').forEach(function (a) {
        a.setAttribute('data-bs-toggle', 'modal');
    });
</script>
@endpush
