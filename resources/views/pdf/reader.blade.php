@extends('layouts.app')

@section('title', 'کتاب‌خوان: ' . $media->name)
@section('page_title', $media->name)

@push('styles')
<style>
    .reader-layout { display: flex; gap: 1rem; align-items: stretch; }
    .reader-side { width: 300px; flex-shrink: 0; display: flex; flex-direction: column; gap: .75rem; }
    .reader-main { flex: 1; min-width: 0; }

    .viewer-box {
        height: calc(100vh - 190px);
        min-height: 480px;
        border: 1px solid var(--key-border, #e5e7eb);
        border-radius: .75rem;
        overflow-y: auto;
        background: #525659;
        direction: ltr;
        position: relative;
    }
    .page-slot { position: relative; margin: 12px auto; box-shadow: 0 2px 10px rgba(0,0,0,.4); background: #fff; }
    .page-slot canvas { display: block; border-radius: 2px; }
    .page-slot .page-no {
        position: absolute; bottom: 6px; inset-inline-end: 10px; direction: rtl;
        background: rgba(0,0,0,.55); color: #fff; font-size: .72rem; padding: 2px 8px; border-radius: 10px;
    }
    .page-slot .spinner-overlay {
        position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: .8rem;
    }
    .textLayer {
        position: absolute; inset: 0; overflow: hidden; line-height: 1; opacity: 1;
    }
    .textLayer ::selection { background: rgba(47,91,234,.4); }
    .textLayer span { position: absolute; white-space: pre; cursor: text; transform-origin: 0 0; color: transparent; }

    .embed-box { width: 100%; height: 100%; border: 0; }

    .hl-dot { width: 12px; height: 12px; border-radius: 50%; display: inline-block; flex-shrink: 0; }
    .hl-yellow { background: #fbbf24; } .hl-green { background: #22c55e; }
    .hl-red { background: #ef4444; } .hl-blue { background: #3b82f6; } .hl-purple { background: #a855f7; }

    .sel-toolbar {
        position: absolute; z-index: 30; display: none; direction: rtl;
        background: #fff; border: 1px solid #e5e7eb; border-radius: .5rem; box-shadow: 0 4px 14px rgba(0,0,0,.18);
        padding: .35rem .5rem;
    }
    @media (max-width: 991px) {
        .reader-layout { flex-direction: column-reverse; }
        .reader-side { width: 100%; }
        .viewer-box { height: 60vh; }
    }
</style>
@endpush

@section('content')
<div class="reader-layout">
    {{-- ستون اصلی کتاب‌خوان --}}
    <div class="reader-main">
        <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-2 no-print">
            <div class="d-flex gap-2 align-items-center">
                <a href="{{ route('pdf.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-right ms-1"></i> فهرست فایل‌ها</a>
                <div class="btn-group btn-group-sm" id="pager">
                    <button type="button" class="btn btn-outline-secondary" id="btnPrev"><i class="bi bi-chevron-up"></i></button>
                    <span class="btn btn-outline-secondary disabled">
                        صفحه <span id="curPage">{{ fa_num($read?->current_page ?? 1) }}</span> از <span id="totalPages">{{ $totalPages ? fa_num($totalPages) : '؟' }}</span>
                    </span>
                    <button type="button" class="btn btn-outline-secondary" id="btnNext"><i class="bi bi-chevron-down"></i></button>
                </div>
                <div class="btn-group btn-group-sm d-none" id="zoomBtns">
                    <button type="button" class="btn btn-outline-secondary" id="zoomOut" title="کوچک‌تر"><i class="bi bi-zoom-out"></i></button>
                    <button type="button" class="btn btn-outline-secondary" id="zoomIn" title="بزرگ‌تر"><i class="bi bi-zoom-in"></i></button>
                </div>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <div class="btn-group btn-group-sm" role="group">
                    <button type="button" class="btn btn-outline-secondary active" id="modeJs"><i class="bi bi-layout-text-window-reverse ms-1"></i> حالت پیشرفته</button>
                    <button type="button" class="btn btn-outline-secondary" id="modeEmbed"><i class="bi bi-file-earmark-pdf ms-1"></i> حالت ساده</button>
                </div>
            </div>
        </div>

        <div class="viewer-box" id="viewerBox">
            {{-- نمایشگر pdf.js --}}
            <div id="jsViewer" class="px-2 py-2"></div>
            {{-- نمایشگر ساده با embed --}}
            <div id="embedViewer" class="h-100 w-100 d-none" style="position:absolute; inset:0">
                <embed src="{{ route('pdf.stream', $media) }}" type="application/pdf" class="embed-box">
            </div>
        </div>
    </div>

    {{-- ستون کناری: پیشرفت + هایلایت‌ها --}}
    <div class="reader-side no-print">
        <x-card bodyClass="py-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="fw-bold small"><i class="bi bi-speedometer text-primary ms-1"></i> پیشرفت مطالعه</span>
                <span class="fw-bold" id="progressLabel">{{ fa_num($read?->progress ?? 0) }}٪</span>
            </div>
            <div class="progress" style="height:10px">
                <div class="progress-bar bg-primary" id="progressBar" style="width: {{ max(2, $read?->progress ?? 2) }}%"></div>
            </div>
            <div class="small text-muted mt-2">
                @if ($read)
                    آخرین مطالعه: {{ fa_datetime($read->last_read_at) }}
                @else
                    این اولین بار است که این فایل را باز می‌کنید؛ پیشرفت به‌طور خودکار ذخیره می‌شود.
                @endif
            </div>
            <div class="d-none mt-2" id="manualPageRow">
                <label class="form-label small mb-1">صفحه فعلی (حالت ساده)</label>
                <div class="input-group input-group-sm">
                    <input type="number" min="1" class="form-control" id="manualPage" value="{{ $read?->current_page ?? 1 }}">
                    <button type="button" class="btn btn-primary" id="manualPageSave">ثبت</button>
                </div>
            </div>
        </x-card>

        <x-card title="افزودن هایلایت / یادداشت" icon="bi-highlighter" bodyClass="py-3">
            <form id="hlForm">
                <div class="row g-2">
                    <div class="col-5">
                        <label class="form-label small">صفحه</label>
                        <input type="number" min="1" class="form-control form-control-sm" name="page" id="hlPage" value="{{ $read?->current_page ?? 1 }}" required>
                    </div>
                    <div class="col-7">
                        <label class="form-label small">رنگ</label>
                        <select class="form-select form-select-sm" name="color" id="hlColor">
                            <option value="yellow">زرد</option>
                            <option value="green">سبز</option>
                            <option value="red">قرمز</option>
                            <option value="blue">آبی</option>
                            <option value="purple">بنفش</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label small">متن انتخاب‌شده</label>
                        <textarea class="form-control form-control-sm" rows="2" name="text" id="hlText" placeholder="متن را در حالت پیشرفته انتخاب کنید یا اینجا بنویسید..." maxlength="2000"></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label small">یادداشت (اختیاری)</label>
                        <textarea class="form-control form-control-sm" rows="2" name="note" id="hlNote" placeholder="یادداشت شخصی شما..." maxlength="1000"></textarea>
                    </div>
                    <div class="col-12 d-grid">
                        <button type="submit" class="btn btn-primary btn-sm" id="hlSave"><i class="bi bi-save ms-1"></i> ذخیره هایلایت</button>
                    </div>
                </div>
                <div class="small text-danger mt-2 d-none" id="hlError"></div>
            </form>
        </x-card>

        <x-card title="هایلایت‌های من" icon="bi-bookmarks" bodyClass="py-0">
            <div style="max-height: 320px; overflow-y: auto" id="hlList">
                @forelse ($highlights as $hl)
                    <div class="p-2 border-bottom small" data-hl="{{ $hl->id }}">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="d-flex align-items-center gap-1">
                                <span class="hl-dot hl-{{ $hl->color }}"></span>
                                <span class="badge badge-soft-secondary">صفحه {{ fa_num($hl->page) }}</span>
                            </span>
                            <button type="button" class="btn btn-link text-danger p-0 small hl-delete" data-id="{{ $hl->id }}" title="حذف"><i class="bi bi-trash3"></i></button>
                        </div>
                        @if ($hl->text)
                            <div class="text-body">{{ $hl->text }}</div>
                        @endif
                        @if ($hl->note)
                            <div class="text-muted fst-italic mt-1"><i class="bi bi-sticky ms-1"></i>{{ $hl->note }}</div>
                        @endif
                    </div>
                @empty
                    <div class="p-3 text-center text-muted small">هایلایتی ثبت نشده است؛ متن را انتخاب کنید یا از فرم بالا یادداشت بگذارید.</div>
                @endforelse
            </div>
        </x-card>
    </div>
</div>

<div class="sel-toolbar" id="selToolbar">
    <button type="button" class="btn btn-sm btn-primary" id="selToHl"><i class="bi bi-highlighter ms-1"></i> هایلایت این متن</button>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/pdf.min.js') }}"></script>
<script>
(function () {
    const mediaId = {{ $media->id }};
    const streamUrl = @json(route('pdf.stream', $media));
    const csrf = document.querySelector('meta[name="csrf-token"]').content;

    const viewerBox = document.getElementById('viewerBox');
    const jsViewer = document.getElementById('jsViewer');
    const embedViewer = document.getElementById('embedViewer');
    const curPageEl = document.getElementById('curPage');
    const totalPagesEl = document.getElementById('totalPages');
    const progressBar = document.getElementById('progressBar');
    const progressLabel = document.getElementById('progressLabel');
    const pager = document.getElementById('pager');
    const zoomBtns = document.getElementById('zoomBtns');
    const manualPageRow = document.getElementById('manualPageRow');
    const hlPage = document.getElementById('hlPage');
    const hlList = document.getElementById('hlList');
    const selToolbar = document.getElementById('selToolbar');

    let totalPages = {{ $totalPages ?: 'null' }};
    let currentPage = {{ $read?->current_page ?? 1 }};
    let scale = null; // fit-width در بار اول
    let pdfDoc = null;
    let progressTimer = null;
    let mode = 'js';

    pdfjsLib.GlobalWorkerOptions.workerSrc = @json(asset('assets/js/pdf.worker.min.js'));

    function faDigits(x) { return String(x).replace(/[0-9]/g, d => '۰۱۲۳۴۵۶۷۸۹'[d]); }

    function updateProgressUi(percent) {
        progressBar.style.width = Math.max(2, percent) + '%';
        progressLabel.textContent = faDigits(percent) + '٪';
    }

    function setCurrentPage(page, save) {
        if (!page || page < 1) return;
        if (totalPages) page = Math.min(page, totalPages);
        currentPage = page;
        curPageEl.textContent = faDigits(page);
        hlPage.value = page;
        if (save) postProgress(page);
    }

    function postProgress(page, pages) {
        const body = { page: page };
        const tp = pages || totalPages;
        if (tp) body.total_pages = tp;
        fetch('{{ route('pdf.progress', $media) }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify(body)
        }).then(r => r.ok ? r.json() : null).then(data => {
            if (data && typeof data.progress === 'number') updateProgressUi(data.progress);
            if (data && data.total_pages && totalPagesEl.textContent === '؟') totalPagesEl.textContent = faDigits(data.total_pages);
        }).catch(() => {});
    }

    // ----------------- حالت پیشرفته pdf.js -----------------
    const rendered = new Set();

    function buildSlots() {
        jsViewer.innerHTML = '';
        for (let i = 1; i <= totalPages; i++) {
            const slot = document.createElement('div');
            slot.className = 'page-slot';
            slot.dataset.page = i;
            slot.dataset.rendered = '0';
            slot.innerHTML = '<div class="spinner-overlay">صفحه ' + faDigits(i) + '...</div>';
            jsViewer.appendChild(slot);
        }
    }

    async function renderPage(num) {
        const slot = jsViewer.querySelector('.page-slot[data-page="' + num + '"]');
        if (!slot || slot.dataset.rendered === '1') return;
        slot.dataset.rendered = '1';

        try {
            const page = await pdfDoc.getPage(num);
            let viewport = page.getViewport({ scale: scale || 1 });

            // اندازه اولیه: جا شدن در عرض
            if (!scale) {
                const boxWidth = viewerBox.clientWidth - 36;
                scale = boxWidth / page.getViewport({ scale: 1 }).width;
                viewport = page.getViewport({ scale: scale });
            }

            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            const dpr = window.devicePixelRatio || 1;
            canvas.width = Math.floor(viewport.width * dpr);
            canvas.height = Math.floor(viewport.height * dpr);
            canvas.style.width = Math.floor(viewport.width) + 'px';
            canvas.style.height = Math.floor(viewport.height) + 'px';

            slot.style.width = Math.floor(viewport.width) + 'px';
            slot.innerHTML = '';
            slot.appendChild(canvas);

            await page.render({ canvasContext: ctx, viewport: viewport, transform: dpr !== 1 ? [dpr, 0, 0, dpr, 0, 0] : null }).promise;

            const no = document.createElement('div');
            no.className = 'page-no';
            no.textContent = 'صفحه ' + faDigits(num) + ' از ' + faDigits(totalPages);
            slot.appendChild(no);

            // لایه متن برای انتخاب
            const textDiv = document.createElement('div');
            textDiv.className = 'textLayer';
            textDiv.style.setProperty('--scale-factor', scale);
            textDiv.style.width = Math.floor(viewport.width) + 'px';
            textDiv.style.height = Math.floor(viewport.height) + 'px';
            slot.appendChild(textDiv);
            const tl = pdfjsLib.renderTextLayer({
                textContentSource: page.streamTextContent(),
                container: textDiv,
                viewport: viewport
            });
            await tl.promise;
        } catch (e) {
            slot.dataset.rendered = '0';
            slot.innerHTML = '<div class="spinner-overlay">خطا در نمایش صفحه ' + faDigits(num) + '</div>';
        }
    }

    const io = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting && mode === 'js') {
                const num = parseInt(entry.target.dataset.page, 10);
                renderPage(num);
            }
        });
    }, { root: viewerBox, rootMargin: '600px 0px' });

    async function initJsViewer() {
        try {
            pdfDoc = await pdfjsLib.getDocument(streamUrl).promise;
            totalPages = pdfDoc.numPages;
            totalPagesEl.textContent = faDigits(totalPages);
            buildSlots();
            jsViewer.querySelectorAll('.page-slot').forEach(s => io.observe(s));

            // پرش به صفحه آخرین مطالعه
            if (currentPage > 1 && currentPage <= totalPages) {
                const target = jsViewer.querySelector('.page-slot[data-page="' + currentPage + '"]');
                if (target) setTimeout(() => target.scrollIntoView({ block: 'start' }), 250);
            }
            postProgress(currentPage, totalPages);
        } catch (e) {
            switchMode('embed');
            const alert = document.createElement('div');
            alert.className = 'alert alert-warning m-3';
            alert.textContent = 'نمایش پیشرفته در این مرورگر فعال نشد؛ به حالت ساده تغییر داده شد.';
            embedViewer.before(alert);
        }
    }

    // صفحه جاری بر اساس نزدیک‌ترین slot به بالای دید
    function detectCurrentPage() {
        if (mode !== 'js' || !totalPages) return;
        const slots = jsViewer.querySelectorAll('.page-slot');
        const boxTop = viewerBox.getBoundingClientRect().top;
        let best = 1, bestDist = Infinity;
        slots.forEach(s => {
            const d = Math.abs(s.getBoundingClientRect().top - boxTop);
            if (d < bestDist) { bestDist = d; best = parseInt(s.dataset.page, 10); }
        });
        if (best !== currentPage) setCurrentPage(best, false);
    }

    viewerBox.addEventListener('scroll', function () {
        if (mode !== 'js') return;
        detectCurrentPage();
        clearTimeout(progressTimer);
        progressTimer = setTimeout(() => postProgress(currentPage), 700);
    }, { passive: true });

    document.getElementById('btnPrev').addEventListener('click', () => goToPage(currentPage - 1));
    document.getElementById('btnNext').addEventListener('click', () => goToPage(currentPage + 1));

    function goToPage(page) {
        if (!totalPages) return;
        page = Math.max(1, Math.min(totalPages, page));
        if (mode === 'js') {
            const slot = jsViewer.querySelector('.page-slot[data-page="' + page + '"]');
            if (slot) slot.scrollIntoView({ block: 'start' });
            setCurrentPage(page, true);
        } else {
            setCurrentPage(page, true);
        }
    }

    document.getElementById('zoomIn').addEventListener('click', () => reScale(scale * 1.2));
    document.getElementById('zoomOut').addEventListener('click', () => reScale(scale / 1.2));

    async function reScale(newScale) {
        if (!pdfDoc || mode !== 'js') return;
        scale = Math.max(0.3, Math.min(4, newScale));
        rendered.clear();
        jsViewer.querySelectorAll('.page-slot').forEach(s => {
            s.dataset.rendered = '0';
            s.innerHTML = '<div class="spinner-overlay">...</div>';
            s.style.width = '';
        });
        for (let i = 1; i <= totalPages; i++) renderPage(i);
    }

    // ----------------- انتخاب متن → هایلایت -----------------
    viewerBox.addEventListener('mouseup', function () {
        const sel = window.getSelection();
        const text = sel ? sel.toString().trim() : '';
        if (text.length > 3 && mode === 'js') {
            const rect = viewerBox.getBoundingClientRect();
            selToolbar.style.display = 'block';
            selToolbar.style.top = Math.max(0, rect.height / 3) + 'px';
            selToolbar.style.left = '50%';
            selToolbar.style.transform = 'translateX(-50%)';
            selToolbar.dataset.text = text.slice(0, 2000);
        } else {
            selToolbar.style.display = 'none';
        }
    });

    document.getElementById('selToHl').addEventListener('click', function () {
        const text = selToolbar.dataset.text || '';
        document.getElementById('hlText').value = text;
        hlPage.value = currentPage;
        selToolbar.style.display = 'none';
        document.getElementById('hlText').focus();
        document.getElementById('hlForm').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    });

    // ----------------- ذخیره و حذف هایلایت -----------------
    function addHlToDom(hl) {
        const empty = hlList.querySelector('.text-center.text-muted');
        if (empty) empty.remove();

        const div = document.createElement('div');
        div.className = 'p-2 border-bottom small';
        div.dataset.hl = hl.id;

        const head = document.createElement('div');
        head.className = 'd-flex justify-content-between align-items-center mb-1';
        head.innerHTML =
            '<span class="d-flex align-items-center gap-1">' +
              '<span class="hl-dot hl-' + hl.color + '"></span>' +
              '<span class="badge badge-soft-secondary">صفحه ' + faDigits(hl.page) + '</span>' +
            '</span>' +
            '<button type="button" class="btn btn-link text-danger p-0 small hl-delete" title="حذف"><i class="bi bi-trash3"></i></button>';
        head.querySelector('.hl-delete').dataset.id = hl.id;
        div.appendChild(head);

        if (hl.text) {
            const t = document.createElement('div');
            t.className = 'text-body';
            t.textContent = hl.text;
            div.appendChild(t);
        }
        if (hl.note) {
            const n = document.createElement('div');
            n.className = 'text-muted fst-italic mt-1';
            n.textContent = hl.note;
            div.appendChild(n);
        }

        hlList.prepend(div);
        bindDelete(div.querySelector('.hl-delete'));
    }

    const deleteUrlBase = '{{ url('pdf') }}/' + mediaId + '/highlight/';

    function bindDelete(btn) {
        if (!btn) return;
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            fetch(deleteUrlBase + id + '/delete', {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }
            }).then(r => r.json()).then(data => {
                if (data.ok) {
                    const node = hlList.querySelector('[data-hl="' + id + '"]');
                    if (node) node.remove();
                }
            }).catch(() => {});
        });
    }

    hlList.querySelectorAll('.hl-delete').forEach(bindDelete);

    document.getElementById('hlForm').addEventListener('submit', async function (e) {
        e.preventDefault();
        const errBox = document.getElementById('hlError');
        errBox.classList.add('d-none');

        const payload = {
            page: parseInt(document.getElementById('hlPage').value, 10) || currentPage,
            text: document.getElementById('hlText').value.trim(),
            note: document.getElementById('hlNote').value.trim(),
            color: document.getElementById('hlColor').value
        };

        if (!payload.text && !payload.note) {
            errBox.textContent = 'متن یا یادداشت را وارد کنید.';
            errBox.classList.remove('d-none');
            return;
        }

        try {
            const res = await fetch('{{ route('pdf.highlight', $media) }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.ok) {
                addHlToDom(data.highlight);
                document.getElementById('hlText').value = '';
                document.getElementById('hlNote').value = '';
            } else {
                errBox.textContent = data.message || 'ذخیره ناموفق بود.';
                errBox.classList.remove('d-none');
            }
        } catch (err) {
            errBox.textContent = 'ذخیره ناموفق بود: ' + err.message;
            errBox.classList.remove('d-none');
        }
    });

    // ----------------- حالت ساده (embed) -----------------
    const modeJsBtn = document.getElementById('modeJs');
    const modeEmbedBtn = document.getElementById('modeEmbed');

    function switchMode(m) {
        mode = m;
        if (m === 'js') {
            jsViewer.classList.remove('d-none');
            embedViewer.classList.add('d-none');
            pager.classList.remove('d-none');
            zoomBtns.classList.remove('d-none');
            manualPageRow.classList.add('d-none');
            modeJsBtn.classList.add('active');
            modeEmbedBtn.classList.remove('active');
            if (pdfDoc && !jsViewer.querySelector('.page-slot')) initJsViewer();
        } else {
            jsViewer.classList.add('d-none');
            embedViewer.classList.remove('d-none');
            pager.classList.add('d-none');
            zoomBtns.classList.add('d-none');
            manualPageRow.classList.remove('d-none');
            modeJsBtn.classList.remove('active');
            modeEmbedBtn.classList.add('active');
            selToolbar.style.display = 'none';
        }
    }

    modeJsBtn.addEventListener('click', () => switchMode('js'));
    modeEmbedBtn.addEventListener('click', () => switchMode('embed'));

    document.getElementById('manualPageSave').addEventListener('click', () => {
        const p = parseInt(document.getElementById('manualPage').value, 10) || 1;
        setCurrentPage(p, true);
    });

    // در حالت ساده، اسکرول پنجره هم پیشرفت را ثبت می‌کند
    window.addEventListener('scroll', function () {
        if (mode !== 'embed' || !totalPages) return;
        const doc = document.documentElement;
        if (doc.scrollHeight <= window.innerHeight) return;
        const frac = window.scrollY / (doc.scrollHeight - window.innerHeight);
        const page = Math.max(1, Math.round(frac * totalPages + 0.5));
        if (page !== currentPage) setCurrentPage(page, false);
        clearTimeout(progressTimer);
        progressTimer = setTimeout(() => postProgress(currentPage), 900);
    }, { passive: true });

    // شروع
    initJsViewer();
})();
</script>
@endpush
