@extends('layouts.app')

@section('title', 'گفتگو: ' . ($room->name ?: 'اتاق گفتگو'))
@section('page_title', 'گفتگو')

@php
    $typeIcons = ['private' => 'bi-person', 'group' => 'bi-people', 'class' => 'bi-mortarboard'];
    $typeLabels = ['private' => 'گفتگوی خصوصی', 'group' => 'گروه', 'class' => 'کلاس'];
    $roomTitle = $room->type === 'private' ? ($members->firstWhere('id', '!=', auth()->id())?->name ?? $members->first()?->name ?? 'گفتگو') : ($room->name ?: ($typeLabels[$room->type] ?? 'گفتگو'));
@endphp

@section('content')
<div id="chatApp"
     data-room-id="{{ $room->id }}"
     data-fetch-url="{{ route('chat.fetch', $room) }}"
     data-send-url="{{ route('chat.send', $room) }}"
     data-vote-url="{{ route('chat.vote', ['room' => $room->id, 'message' => 'MSGID']) }}"
     data-me="{{ auth()->id() }}"
     data-my-name="{{ auth()->user()->name }}">
</div>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('chat.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-right"></i></a>
        <h5 class="fw-bold mb-0 text-break">
            <i class="bi {{ $typeIcons[$room->type] ?? 'bi-chat' }} text-primary"></i>
            {{ $roomTitle }}
        </h5>
        <span class="badge badge-soft-secondary">{{ $typeLabels[$room->type] ?? $room->type }}</span>
    </div>
    <div class="d-flex gap-2">
        <button type="button" id="wbToggle" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-easel"></i> وایت‌برد
        </button>
        <button type="button" class="btn btn-outline-secondary btn-sm d-lg-none" data-bs-toggle="collapse" data-bs-target="#membersCard">
            <i class="bi bi-people"></i> اعضا
        </button>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-9">
        <div class="card mb-0 position-relative overflow-hidden">
            <div class="card-body p-0">
                {{-- پیام‌ها --}}
                <div id="messages" class="chat-box" style="height: 58vh">
                    <div id="msgLoading" class="text-center text-muted small py-5">
                        <div class="spinner-border spinner-border-sm text-primary"></div>
                        در حال دریافت پیام‌ها...
                    </div>
                </div>

                {{-- وایت‌برد --}}
                <div id="wbPanel" class="d-none wb-panel">
                    <div class="d-flex justify-content-between align-items-center p-2 border-bottom flex-wrap gap-2 bg-body-tertiary">
                        <div class="d-flex gap-2 align-items-center flex-wrap">
                            <strong class="small"><i class="bi bi-easel text-primary"></i> وایت‌برد مشترک</strong>
                            <label class="small d-flex align-items-center gap-1">
                                قلم
                                <input type="color" id="wbColor" value="#2f5bea" class="form-control form-control-color form-control-sm">
                            </label>
                            <select id="wbSize" class="form-select form-select-sm" style="width:auto">
                                <option value="2">باریک</option>
                                <option value="4" selected>متوسط</option>
                                <option value="8">ضخیم</option>
                            </select>
                            <button type="button" id="wbEraser" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eraser"></i> پاک‌کن</button>
                            <button type="button" id="wbClear" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> پاک‌کردن همه</button>
                        </div>
                        <button type="button" id="wbClose" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-lg"></i> بستن</button>
                    </div>
                    <div class="p-2">
                        <canvas id="wbCanvas" width="1000" height="560" class="wb-canvas"></canvas>
                        <div class="small text-muted mt-1">نقاشی شما بلافاصله برای همه اعضای اتاق نمایش داده می‌شود.</div>
                    </div>
                </div>
            </div>

            {{-- نوار ابزار و ارسال --}}
            <div class="border-top p-2 p-md-3">
                <div id="recordBar" class="d-none align-items-center gap-2 mb-2 small bg-danger-subtle border border-danger-subtle rounded p-2">
                    <span class="badge bg-danger"><i class="bi bi-record-fill"></i> در حال ضبط</span>
                    <span id="recordTime">۰ ثانیه</span>
                    <div class="flex-grow-1"></div>
                    <button type="button" id="recordCancel" class="btn btn-sm btn-outline-secondary">لغو</button>
                    <button type="button" id="recordStop" class="btn btn-sm btn-danger"><i class="bi bi-send"></i> ارسال صوت</button>
                </div>

                {{-- پنل نظرسنجی --}}
                <div id="pollPanel" class="d-none border rounded p-2 p-md-3 mb-2 bg-body-tertiary">
                    <h6 class="small fw-bold mb-2"><i class="bi bi-bar-chart text-primary"></i> ساخت نظرسنجی</h6>
                    <input type="text" id="pollQuestion" class="form-control form-control-sm mb-2" placeholder="سؤال نظرسنجی..." maxlength="200">
                    <div id="pollOptions" class="d-flex flex-column gap-2 mb-2"></div>
                    <div class="d-flex gap-2">
                        <button type="button" id="pollAddOption" class="btn btn-sm btn-outline-secondary"><i class="bi bi-plus"></i> افزودن گزینه</button>
                        <button type="button" id="pollCancel" class="btn btn-sm btn-light">انصراف</button>
                        <button type="button" id="pollSend" class="btn btn-sm btn-primary"><i class="bi bi-send"></i> ارسال نظرسنجی</button>
                    </div>
                </div>

                <form id="composerForm" class="d-flex gap-2 align-items-center composer" autocomplete="off">
                    <input type="file" id="attachInput" class="d-none">
                    <input type="text" id="composerInput" name="body" class="form-control" placeholder="پیام خود را بنویسید..." maxlength="5000">
                    <button type="button" id="attachBtn" class="btn btn-outline-secondary" title="ارسال فایل یا تصویر"><i class="bi bi-paperclip"></i></button>
                    <button type="button" id="recordBtn" class="btn btn-outline-secondary" title="ضبط پیام صوتی"><i class="bi bi-mic"></i></button>
                    <button type="button" id="pollBtn" class="btn btn-outline-secondary" title="نظرسنجی جدید"><i class="bi bi-bar-chart"></i></button>
                    <button type="submit" class="btn btn-primary" title="ارسال پیام"><i class="bi bi-send"></i></button>
                </form>
            </div>
        </div>
    </div>

    {{-- اعضا --}}
    <div class="col-lg-3">
        <div class="collapse d-lg-block" id="membersCard">
            <x-card title="اعضای اتاق" icon="bi-people" bodyClass="py-2 px-3">
                @forelse ($members as $member)
                    <div class="d-flex align-items-center gap-2 py-1 {{ $member->id === auth()->id() ? 'text-primary fw-bold' : '' }}">
                        <span class="avatar">{{ mb_substr($member->name, 0, 1) }}</span>
                        <span class="small text-break">{{ $member->name }}</span>
                        @if ($member->id === auth()->id())
                            <span class="badge badge-soft-primary ms-auto">شما</span>
                        @endif
                    </div>
                @empty
                    <div class="small text-muted py-2">عضوی یافت نشد.</div>
                @endforelse
            </x-card>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .composer .btn { flex-shrink: 0; }
    .wb-panel { position: absolute; top: 0; inset-inline: 0; height: 58vh; background: var(--key-card-bg, #fff); z-index: 5; overflow: auto; }
    .wb-canvas { width: 100%; height: auto; border: 1px solid var(--key-border, #e5e9f2); border-radius: 10px; touch-action: none; cursor: crosshair; background: #fff; }
</style>
@endpush

@push('scripts')
<script src="{{ asset('assets/js/chat.js') }}"></script>
@endpush
