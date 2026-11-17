/* چت آنلاین کلید (KEY) — polling، پیام متنی/فایل/تصویر/صوت، نظرسنجی و وایت‌برد مشترک */
(function () {
    'use strict';

    const app = document.getElementById('chatApp');
    if (!app) return;

    const CFG = {
        roomId: app.dataset.roomId,
        fetchUrl: app.dataset.fetchUrl,
        sendUrl: app.dataset.sendUrl,
        voteUrl: app.dataset.voteUrl, // شامل نگه‌دارندهٔ :msgId:
        me: Number(app.dataset.me),
    };

    const box = document.getElementById('messages');
    const form = document.getElementById('composerForm');
    const input = document.getElementById('composerInput');
    const attachBtn = document.getElementById('attachBtn');
    const attachInput = document.getElementById('attachInput');
    const recordBtn = document.getElementById('recordBtn');
    const recordBar = document.getElementById('recordBar');
    const recordTime = document.getElementById('recordTime');
    const recordStop = document.getElementById('recordStop');
    const recordCancel = document.getElementById('recordCancel');
    const pollBtn = document.getElementById('pollBtn');
    const pollPanel = document.getElementById('pollPanel');
    const pollQuestion = document.getElementById('pollQuestion');
    const pollOptionsBox = document.getElementById('pollOptions');
    const pollAddOption = document.getElementById('pollAddOption');
    const pollCancel = document.getElementById('pollCancel');
    const pollSend = document.getElementById('pollSend');
    const wbToggle = document.getElementById('wbToggle');
    const wbPanel = document.getElementById('wbPanel');
    const wbClose = document.getElementById('wbClose');
    const wbColor = document.getElementById('wbColor');
    const wbSize = document.getElementById('wbSize');
    const wbEraser = document.getElementById('wbEraser');
    const wbClear = document.getElementById('wbClear');
    const canvas = document.getElementById('wbCanvas');

    let lastId = 0;
    let wbSavedJson = '';
    let wbStrokes = [];
    let wbTool = { color: '#2f5bea', size: 4, eraser: false };
    let drawing = false;
    let curStroke = null;
    let recorder = null;
    let recStream = null;
    let recChunks = [];
    let recStart = 0;
    let recTimer = null;
    const polls = {};

    // ---------------- کمکی‌ها ----------------
    const FA = '۰۱۲۳۴۵۶۷۸۹';
    const faNum = (n) => String(n).replace(/\d/g, (d) => FA[d]);

    function errText(err) {
        return (err && err.data && (err.data.error || err.data.message)) || (err && err.message) || 'خطای نامشخص';
    }

    function el(tag, cls, text) {
        const e = document.createElement(tag);
        if (cls) e.className = cls;
        if (text !== undefined && text !== null && text !== '') e.textContent = text;
        return e;
    }

    function fmtSize(bytes) {
        bytes = Number(bytes || 0);
        if (bytes >= 1048576) return faNum((bytes / 1048576).toFixed(1)) + ' مگابایت';
        if (bytes >= 1024) return faNum(Math.ceil(bytes / 1024)) + ' کیلوبایت';
        return faNum(bytes) + ' بایت';
    }

    function fmtDuration(sec) {
        sec = Number(sec || 0);
        const m = Math.floor(sec / 60);
        const s = String(sec % 60).padStart(2, '0');
        return faNum(m + ':' + s);
    }

    function nearBottom() {
        return box.scrollHeight - box.scrollTop - box.clientHeight < 140;
    }

    function scrollToBottom() {
        box.scrollTop = box.scrollHeight;
    }

    // ---------------- رندر پیام‌ها ----------------
    function renderMessage(m) {
        if (!m) return null;

        // پیام سیستمی
        if (m.type === 'system') {
            let payload = null;
            try { payload = JSON.parse(m.body); } catch (e) { /* متن ساده */ }
            if (payload && payload.wb && Array.isArray(payload.strokes)) {
                applyWhiteboard(payload);
                return null;
            }
            const wrap = el('div', 'd-flex justify-content-center my-2');
            const chip = el('span', 'system-msg small text-muted px-3 py-1 rounded-pill bg-body-tertiary border', m.body || '');
            wrap.appendChild(chip);
            return wrap;
        }

        const wrap = el('div', 'd-flex mb-3 ' + (m.is_mine ? 'justify-content-end' : 'justify-content-start'));
        const bubble = el('div', 'chat-msg ' + (m.is_mine ? 'mine' : 'theirs'));

        const meta = el('div', 'chat-meta');
        const name = el('strong', null, m.user ? m.user.name : 'کاربر');
        const time = el('span', null, m.time || '');
        meta.appendChild(name);
        meta.appendChild(time);
        bubble.appendChild(meta);

        if (m.type === 'text') {
            const body = el('div', 'chat-body');
            body.textContent = m.body || '';
            body.style.whiteSpace = 'pre-wrap';
            bubble.appendChild(body);
        } else if (m.type === 'image') {
            const link = document.createElement('a');
            link.href = m.file_url || '#';
            link.target = '_blank';
            link.rel = 'noopener';
            const img = document.createElement('img');
            img.src = m.file_url || '';
            img.alt = m.file_name || 'تصویر';
            img.className = 'img-fluid rounded';
            img.style.maxHeight = '320px';
            img.loading = 'lazy';
            link.appendChild(img);
            bubble.appendChild(link);
        } else if (m.type === 'audio') {
            const audio = document.createElement('audio');
            audio.controls = true;
            audio.preload = 'metadata';
            audio.src = m.file_url || '';
            audio.className = 'd-block mt-1';
            audio.style.maxWidth = '260px';
            bubble.appendChild(audio);
            if (m.duration_seconds) {
                bubble.appendChild(el('span', 'chat-size', 'مدت: ' + fmtDuration(m.duration_seconds)));
            }
        } else if (m.type === 'file') {
            const link = document.createElement('a');
            link.href = m.file_url || '#';
            link.target = '_blank';
            link.rel = 'noopener';
            link.className = 'chat-file';
            link.innerHTML = '<i class="bi bi-file-earmark-arrow-down"></i> ';
            link.appendChild(el('span', null, m.file_name || 'فایل پیوست'));
            link.appendChild(el('span', 'chat-size', fmtSize(m.file_size)));
            bubble.appendChild(link);
        } else if (m.type === 'poll') {
            polls[m.id] = {
                q: m.body || 'نظرسنجی',
                options: m.poll_options || [],
                counts: m.poll_counts || [],
                total: m.poll_total || 0,
                my: m.my_vote,
            };
            const card = el('div', 'poll-card');
            card.dataset.pollId = m.id;
            drawPoll(card, m.id);
            bubble.appendChild(card);
        }

        wrap.appendChild(bubble);
        return wrap;
    }

    function drawPoll(card, id) {
        const d = polls[id];
        if (!card || !d) return;
        card.innerHTML = '';
        card.appendChild(el('div', 'fw-bold small mb-2', '📊 ' + d.q));
        d.options.forEach(function (opt, i) {
            const count = Number(d.counts[i] || 0);
            const pct = d.total > 0 ? Math.round((count * 100) / d.total) : 0;
            const row = el('div', 'poll-option' + (Number(d.my) === i ? ' mine-vote' : ''));
            const btn = el('button', 'poll-vote' + (Number(d.my) === i ? ' is-my' : ''));
            btn.type = 'button';
            btn.appendChild(el('span', null, (Number(d.my) === i ? '✓ ' : '') + opt));
            btn.addEventListener('click', function () { castVote(id, i); });
            row.appendChild(btn);
            row.appendChild(el('span', 'poll-count small', faNum(count) + ' رأی — ' + faNum(pct) + '٪'));
            const bar = el('div', 'poll-bar');
            const fill = el('div', 'poll-fill');
            fill.style.width = pct + '%';
            bar.appendChild(fill);
            row.appendChild(bar);
            card.appendChild(row);
        });
        card.appendChild(el('div', 'small text-muted mt-1', d.total > 0 ? 'مجموع آرا: ' + faNum(d.total) : 'هنوز رأی‌ای ثبت نشده است.'));
    }

    async function castVote(id, idx) {
        try {
            const res = await window.keyFetch(CFG.voteUrl.replace(':msgId:', id), {
                method: 'POST',
                json: { option_index: idx },
            });
            const d = polls[id];
            if (d) {
                if (res.counts) d.counts = res.counts;
                d.total = res.total || 0;
                d.my = res.my_vote;
                drawPoll(document.querySelector('.poll-card[data-poll-id="' + id + '"]'), id);
            }
            window.keyToast('رأی شما ثبت شد.', 'success');
        } catch (err) {
            window.keyToast(errText(err), 'danger');
        }
    }

    // ---------------- دریافت و polling ----------------
    async function fetchNew() {
        try {
            const data = await window.keyFetch(CFG.fetchUrl + '?after_id=' + lastId);
            const stick = nearBottom();
            (data.messages || []).forEach(function (m) {
                const wrap = renderMessage(m);
                if (wrap) box.appendChild(wrap);
            });
            if (data.whiteboard) applyWhiteboard(data.whiteboard);
            if (Number(data.last_id) > lastId) lastId = Number(data.last_id);
            if (stick) scrollToBottom();
        } catch (err) {
            console.warn('chat polling failed:', errText(err)); // خطاهای polling بی‌صدا
        }
    }

    async function initialLoad() {
        const loading = document.getElementById('msgLoading');
        try {
            const data = await window.keyFetch(CFG.fetchUrl + '?after_id=0');
            if (loading) loading.remove();
            (data.messages || []).forEach(function (m) {
                const wrap = renderMessage(m);
                if (wrap) box.appendChild(wrap);
            });
            if (data.whiteboard) applyWhiteboard(data.whiteboard);
            lastId = Number(data.last_id || 0);
            scrollToBottom();
        } catch (err) {
            if (loading) loading.textContent = 'خطا در دریافت پیام‌ها: ' + errText(err);
        }
        setInterval(fetchNew, 3000);
    }

    // ---------------- ارسال پیام ----------------
    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        const body = input.value.trim();
        if (!body) return;
        try {
            const fd = new FormData();
            fd.append('type', 'text');
            fd.append('body', body);
            await window.keyFetch(CFG.sendUrl, { method: 'POST', body: fd });
            input.value = '';
            fetchNew();
        } catch (err) {
            window.keyToast(errText(err), 'danger');
        }
    });

    attachBtn.addEventListener('click', function () { attachInput.click(); });
    attachInput.addEventListener('change', async function () {
        const file = attachInput.files && attachInput.files[0];
        attachInput.value = '';
        if (!file) return;
        if (file.size > 10 * 1024 * 1024) return window.keyToast('حجم فایل حداکثر ۱۰ مگابایت است.', 'danger');
        const isImage = file.type && file.type.indexOf('image/') === 0;
        const fd = new FormData();
        fd.append('type', isImage ? 'image' : 'file');
        fd.append('attachment', file, file.name);
        try {
            await window.keyFetch(CFG.sendUrl, { method: 'POST', body: fd });
            fetchNew();
        } catch (err) {
            window.keyToast(errText(err), 'danger');
        }
    });

    // ---------------- ضبط صدا ----------------
    recordBtn.addEventListener('click', async function () {
        if (recorder) return;
        if (!window.MediaRecorder || !navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            return window.keyToast('مرورگر شما از ضبط صدا پشتیبانی نمی‌کند.', 'danger');
        }
        try {
            recStream = await navigator.mediaDevices.getUserMedia({ audio: true });
        } catch (e) {
            return window.keyToast('دسترسی به میکروفون داده نشد.', 'danger');
        }
        const mime = MediaRecorder.isTypeSupported('audio/webm;codecs=opus') ? 'audio/webm;codecs=opus' : 'audio/webm';
        recChunks = [];
        recorder = new MediaRecorder(recStream, { mimeType: mime });
        recorder.ondataavailable = function (ev) { if (ev.data && ev.data.size) recChunks.push(ev.data); };
        recorder.start();
        recStart = Date.now();
        recordBar.classList.remove('d-none');
        recordBar.classList.add('d-flex');
        recordTime.textContent = faNum(0) + ' ثانیه';
        recTimer = setInterval(function () {
            recordTime.textContent = faNum(Math.floor((Date.now() - recStart) / 1000)) + ' ثانیه';
        }, 500);
    });

    function stopRecord(send) {
        if (!recorder) return;
        const duration = Math.floor((Date.now() - recStart) / 1000);
        clearInterval(recTimer);
        recordBar.classList.add('d-none');
        recordBar.classList.remove('d-flex');
        const chunks = recChunks;
        const stream = recStream;
        recChunks = [];
        recStream = null;
        const rec = recorder;
        recorder = null;
        rec.onstop = function () {
            if (stream) stream.getTracks().forEach(function (t) { t.stop(); });
            if (!send) return;
            const blob = new Blob(chunks, { type: 'audio/webm' });
            if (!blob.size) return window.keyToast('ضبط صدا ناموفق بود.', 'danger');
            const fd = new FormData();
            fd.append('type', 'audio');
            fd.append('attachment', blob, 'voice-' + Date.now() + '.webm');
            if (duration >= 1 && duration <= 600) fd.append('duration', String(duration));
            window.keyFetch(CFG.sendUrl, { method: 'POST', body: fd })
                .then(function () { fetchNew(); })
                .catch(function (err) { window.keyToast(errText(err), 'danger'); });
        };
        rec.stop();
    }

    recordStop.addEventListener('click', function () { stopRecord(true); });
    recordCancel.addEventListener('click', function () { stopRecord(false); });

    // ---------------- نظرسنجی ----------------
    function addPollRow(value) {
        const row = el('div', 'input-group input-group-sm');
        const inp = document.createElement('input');
        inp.type = 'text';
        inp.className = 'form-control';
        inp.placeholder = 'متن گزینه';
        inp.maxLength = 120;
        if (value) inp.value = value;
        const del = document.createElement('button');
        del.type = 'button';
        del.className = 'btn btn-outline-danger';
        del.innerHTML = '<i class="bi bi-x"></i>';
        del.title = 'حذف گزینه';
        del.addEventListener('click', function () {
            if (pollOptionsBox.children.length > 2) row.remove();
            else window.keyToast('نظرسنجی باید حداقل دو گزینه داشته باشد.', 'warning');
        });
        row.appendChild(inp);
        row.appendChild(del);
        pollOptionsBox.appendChild(row);
    }

    pollBtn.addEventListener('click', function () {
        const opening = pollPanel.classList.contains('d-none');
        pollPanel.classList.toggle('d-none');
        if (opening && !pollOptionsBox.children.length) {
            addPollRow();
            addPollRow();
        }
        if (opening) pollQuestion.focus();
    });

    pollAddOption.addEventListener('click', function () {
        if (pollOptionsBox.children.length >= 10) return window.keyToast('حداکثر ۱۰ گزینه مجاز است.', 'warning');
        addPollRow();
    });

    pollCancel.addEventListener('click', function () {
        pollPanel.classList.add('d-none');
        pollQuestion.value = '';
        pollOptionsBox.innerHTML = '';
    });

    pollSend.addEventListener('click', async function () {
        const q = pollQuestion.value.trim();
        const opts = Array.prototype.map.call(pollOptionsBox.querySelectorAll('input'), function (i) { return i.value.trim(); })
            .filter(function (v) { return v; });
        if (!q) return window.keyToast('سؤال نظرسنجی را وارد کنید.', 'danger');
        if (opts.length < 2) return window.keyToast('نظرسنجی باید حداقل دو گزینه داشته باشد.', 'danger');
        const fd = new FormData();
        fd.append('type', 'poll');
        fd.append('poll_question', q);
        opts.forEach(function (o) { fd.append('poll_options[]', o); });
        try {
            await window.keyFetch(CFG.sendUrl, { method: 'POST', body: fd });
            pollCancel.click();
            fetchNew();
        } catch (err) {
            window.keyToast(errText(err), 'danger');
        }
    });

    // ---------------- وایت‌برد ----------------
    const ctx = canvas ? canvas.getContext('2d') : null;

    function canvasPos(ev) {
        const r = canvas.getBoundingClientRect();
        return [
            Math.round((ev.clientX - r.left) * (canvas.width / r.width)),
            Math.round((ev.clientY - r.top) * (canvas.height / r.height)),
        ];
    }

    function drawStroke(context, s) {
        context.save();
        if (s.e) {
            context.globalCompositeOperation = 'destination-out';
            context.lineWidth = (s.w || 4) * 4;
        } else {
            context.globalCompositeOperation = 'source-over';
            context.lineWidth = s.w || 4;
        }
        context.strokeStyle = s.c || '#111';
        context.lineCap = 'round';
        context.lineJoin = 'round';
        context.beginPath();
        s.p.forEach(function (pt, i) {
            if (i === 0) context.moveTo(pt[0], pt[1]);
            else context.lineTo(pt[0], pt[1]);
        });
        if (s.p.length === 1) {
            context.lineTo(s.p[0][0] + 0.5, s.p[0][1] + 0.5);
        }
        context.stroke();
        context.restore();
    }

    function redrawWb() {
        if (!ctx) return;
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        wbStrokes.forEach(function (s) { drawStroke(ctx, s); });
    }

    function applyWhiteboard(payload) {
        if (!payload || !Array.isArray(payload.strokes)) return;
        const j = JSON.stringify(payload);
        if (j === wbSavedJson) return;
        wbSavedJson = j;
        wbStrokes = payload.strokes.slice(-300);
        redrawWb();
    }

    async function pushWb() {
        const payload = { wb: true, strokes: wbStrokes };
        const j = JSON.stringify(payload);
        wbSavedJson = j;
        try {
            const fd = new FormData();
            fd.append('type', 'system');
            fd.append('body', j);
            await window.keyFetch(CFG.sendUrl, { method: 'POST', body: fd });
        } catch (err) {
            window.keyToast(errText(err), 'danger');
        }
    }

    if (canvas) {
        wbToggle.addEventListener('click', function () {
            wbPanel.classList.toggle('d-none');
            if (!wbPanel.classList.contains('d-none')) redrawWb();
        });
        wbClose.addEventListener('click', function () { wbPanel.classList.add('d-none'); });
        wbColor.addEventListener('input', function (e) {
            wbTool.color = e.target.value;
            wbTool.eraser = false;
            wbEraser.classList.remove('active');
        });
        wbSize.addEventListener('change', function (e) { wbTool.size = Number(e.target.value) || 4; });
        wbEraser.addEventListener('click', function () {
            wbTool.eraser = !wbTool.eraser;
            wbEraser.classList.toggle('active', wbTool.eraser);
        });
        wbClear.addEventListener('click', function () {
            wbStrokes = [];
            redrawWb();
            pushWb();
        });

        canvas.addEventListener('pointerdown', function (ev) {
            drawing = true;
            try { canvas.setPointerCapture(ev.pointerId); } catch (e) { /* بی‌اهمیت */ }
            curStroke = { c: wbTool.color, w: wbTool.size, e: wbTool.eraser ? 1 : 0, p: [canvasPos(ev)] };
        });
        canvas.addEventListener('pointermove', function (ev) {
            if (!drawing || !curStroke) return;
            curStroke.p.push(canvasPos(ev));
            drawStroke(ctx, curStroke);
        });
        window.addEventListener('pointerup', function () {
            if (!drawing || !curStroke) return;
            drawing = false;
            if (curStroke.p.length > 0) {
                wbStrokes.push(curStroke);
                if (wbStrokes.length > 300) wbStrokes = wbStrokes.slice(-300);
                pushWb();
            }
            curStroke = null;
        });
    }

    initialLoad();
})();
