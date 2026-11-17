@extends('layouts.app')

@section('title', 'دستیار هوش مصنوعی')
@section('page_title', 'دستیار هوش مصنوعی — مربی مطالعه')

@push('styles')
<style>
    .ai-layout { display: flex; gap: 1rem; align-items: stretch; min-height: calc(100vh - 220px); }
    .ai-sidebar { width: 260px; flex-shrink: 0; }
    .ai-main { flex: 1; display: flex; flex-direction: column; min-width: 0; }
    .ai-thread { flex: 1; overflow-y: auto; padding: 1rem; display: flex; flex-direction: column; gap: .75rem; }
    .ai-msg { max-width: 78%; padding: .65rem 1rem; border-radius: 1rem; white-space: pre-wrap; line-height: 1.9; font-size: .925rem; }
    .ai-msg.user { align-self: flex-start; background: var(--key-primary, #2f5bea); color: #fff; border-start-start-radius: .3rem; }
    .ai-msg.assistant { align-self: flex-end; background: var(--key-card-bg, #fff); border: 1px solid var(--key-border, #e5e7eb); border-start-end-radius: .3rem; }
    .ai-msg .msg-time { font-size: .68rem; opacity: .7; margin-top: .25rem; }
    .ai-typing { display: none; }
    @media (max-width: 991px) {
        .ai-layout { flex-direction: column; min-height: auto; }
        .ai-sidebar { width: 100%; }
        .ai-thread { min-height: 340px; }
    }
</style>
@endpush

@section('content')
<div class="ai-layout">
    {{-- فهرست گفتگوها --}}
    <div class="ai-sidebar">
        <x-card title="گفتگوها" icon="bi-chat-dots" bodyClass="py-0">
            <div style="max-height: 420px; overflow-y: auto" id="convList">
                @forelse ($conversations as $conv)
                    <a href="{{ route('ai.index', ['c' => $conv->id]) }}"
                       class="d-block px-3 py-2 border-bottom text-decoration-none {{ $conversation && $conversation->id === $conv->id ? 'bg-primary-subtle fw-bold' : 'text-body' }}">
                        <div class="small text-truncate">
                            @if ($conv->context_type === 'report')
                                <i class="bi bi-file-earmark-bar-graph text-info ms-1"></i>
                            @else
                                <i class="bi bi-chat-left-text text-primary ms-1"></i>
                            @endif
                            {{ $conv->title ?: 'گفتگوی بدون عنوان' }}
                        </div>
                        <div class="small text-muted">{{ fa_num($conv->messages_count) }} پیام — {{ fa_date($conv->updated_at?->toDateString()) }}</div>
                    </a>
                @empty
                    <div class="p-3 text-center text-muted small">هنوز گفتگویی ندارید؛ اولین پیام را بفرستید.</div>
                @endforelse
            </div>
        </x-card>

        <x-card title="ابزارها" icon="bi-lightning">
            <div class="d-grid gap-2">
                <a href="{{ route('ai.report') }}" class="btn btn-outline-info btn-sm"><i class="bi bi-file-earmark-bar-graph ms-1"></i> گزارش عملکرد هوشمند</a>
                <a href="{{ route('analytics.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-graph-up-arrow ms-1"></i> تحلیل عملکرد</a>
            </div>
        </x-card>
    </div>

    {{-- رشته پیام + فرم ارسال --}}
    <div class="ai-main">
        <div class="card mb-0 d-flex flex-column flex-grow-1" style="min-height: 480px">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">
                    <i class="bi bi-robot text-primary ms-1"></i>
                    {{ $conversation?->title ?: 'گفتگوی جدید' }}
                </h6>
                <span class="badge rounded-pill {{ $aiReady ? 'badge-soft-success' : 'badge-soft-warning' }}">
                    {{ $aiReady ? 'متصل به سرویس AI' : 'حالت قانون‌محور (AI تنظیم نشده)' }}
                </span>
            </div>

            <div class="ai-thread" id="thread" data-conversation="{{ $conversation?->id ?? '' }}">
                @forelse ($messages as $message)
                    <div class="ai-msg {{ $message->role === 'user' ? 'user' : 'assistant' }}">
                        {{ $message->content }}
                        <div class="msg-time">{{ fa_datetime($message->created_at) }}</div>
                    </div>
                @empty
                    <div class="text-center text-muted my-auto">
                        <i class="bi bi-robot" style="font-size:2.4rem"></i>
                        <h6 class="mt-2 mb-1">با مربی مطالعه خود صحبت کنید</h6>
                        <p class="small mb-0">مثلاً: «برنامه مطالعه این هفته من برای زیست چطور باشد؟» یا «چطور تست فیزیک را سریع‌تر بزنم؟»</p>
                    </div>
                @endforelse
            </div>

            <div class="card-footer">
                <div class="ai-typing small text-muted mb-2" id="typing"><i class="bi bi-three-dots"></i> مربی در حال نوشتن...</div>
                <form id="chatForm" method="POST" action="{{ route('ai.chat') }}">
                    @csrf
                    <input type="hidden" name="conversation_id" id="convInput" value="{{ $conversation?->id ?? '' }}">
                    <div class="input-group">
                        <textarea name="message" id="msgInput" class="form-control" rows="2" placeholder="پیام خود را بنویسید... (Ctrl+Enter برای ارسال)"
                                  required minlength="2" maxlength="4000"></textarea>
                        <button type="submit" class="btn btn-primary" id="sendBtn"><i class="bi bi-send ms-1"></i> ارسال</button>
                    </div>
                </form>
                <div class="small text-danger mt-1 d-none" id="chatError"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const thread = document.getElementById('thread');
    const form = document.getElementById('chatForm');
    const input = document.getElementById('msgInput');
    const convInput = document.getElementById('convInput');
    const sendBtn = document.getElementById('sendBtn');
    const typing = document.getElementById('typing');
    const errorBox = document.getElementById('chatError');
    const csrf = document.querySelector('meta[name="csrf-token"]').content;

    function scrollBottom() { thread.scrollTop = thread.scrollHeight; }
    scrollBottom();

    function addMsg(role, content, time) {
        const div = document.createElement('div');
        div.className = 'ai-msg ' + (role === 'user' ? 'user' : 'assistant');
        div.textContent = content;
        const t = document.createElement('div');
        t.className = 'msg-time';
        t.textContent = time || '';
        div.appendChild(t);
        thread.appendChild(div);
        scrollBottom();
    }

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        const message = input.value.trim();
        if (message.length < 2) return;

        errorBox.classList.add('d-none');
        input.value = '';
        sendBtn.disabled = true;
        typing.style.display = 'block';
        addMsg('user', message);

        try {
            const res = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    message: message,
                    conversation_id: convInput.value || null
                })
            });

            if (!res.ok) throw new Error('HTTP ' + res.status);
            const data = await res.json();

            if (data.conversation_id) {
                convInput.value = data.conversation_id;
                history.replaceState(null, '', '{{ url('ai') }}?c=' + data.conversation_id);
            }
            typing.style.display = 'none';
            addMsg('assistant', data.reply.content, data.reply.time);
        } catch (err) {
            typing.style.display = 'none';
            errorBox.textContent = 'ارسال پیام ناموفق بود: ' + err.message + ' — می‌توانید فرم را دوباره ارسال کنید.';
            errorBox.classList.remove('d-none');
        } finally {
            sendBtn.disabled = false;
            input.focus();
        }
    });

    input.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            form.dispatchEvent(new Event('submit', {cancelable: true}));
        }
    });
})();
</script>
@endpush
