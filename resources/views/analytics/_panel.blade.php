{{-- پنل مشترک تحلیل عملکرد: در index (خود کاربر) و user (مشاهده دانشجوی خاص) استفاده می‌شود --}}

<div class="row g-3 mb-2">
    <div class="col-6 col-md-3">
        <x-stat icon="bi-clipboard-data" label="تعداد آزمون‌های تمام‌شده" value="{{ fa_num($attemptsCount) }}" color="primary"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-bullseye" label="میانگین درصد آزمون‌ها" value="{{ fa_num($avgPercent) }}٪" color="success"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-check2-square" label="دقت کلی پاسخ‌گویی" value="{{ fa_num($overallAccuracy) }}٪" color="info"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-hourglass-split" label="مرورهای عقب‌افتاده" value="{{ fa_num($overdue) }}" color="{{ $overdue > 0 ? 'danger' : 'success' }}"/>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <x-card title="روند میانگین درصد آزمون‌ها" icon="bi-graph-up-arrow">
            @if ($trend->isEmpty())
                <x-empty icon="bi-graph-up-arrow" title="داده آزمونی وجود ندارد"
                         message="با شرکت در آزمون‌ها، روند عملکرد شما اینجا نمایش داده می‌شود."/>
            @else
                <div style="height: 260px"><canvas id="trendChart"></canvas></div>
            @endif
        </x-card>

        <x-card title="دقت پاسخ‌گویی به تفکیک درس" icon="bi-bar-chart">
            @if ($subjectAccuracy->isEmpty())
                <x-empty icon="bi-bar-chart" title="پاسخ‌برگه‌ای ثبت نشده"
                         message="دقت هر درس از پاسخ‌های آزمون‌های تمام‌شده محاسبه می‌شود."/>
            @else
                <div style="height: 260px"><canvas id="accuracyChart"></canvas></div>
            @endif
        </x-card>

        <x-card title="دقیقه‌های مطالعه در ۱۴ روز اخیر" icon="bi-alarm">
            <div style="height: 240px"><canvas id="studyChart"></canvas></div>
            <div class="small text-muted mt-2">
                مجموع دو هفته: <span class="fw-bold text-body">{{ duration_human($studyTotal) }}</span>
                | میانگین روزانه: <span class="fw-bold text-body">{{ duration_human(round($studyTotal / 14)) }}</span>
            </div>
        </x-card>
    </div>

    <div class="col-lg-4">
        <x-card title="نقاط قوت و ضعف (سطح تسلط)" icon="bi-speedometer2" bodyClass="py-0">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light"><tr><th>درس</th><th>تسلط</th><th>تست‌ها</th></tr></thead>
                    <tbody>
                    @forelse ($mastery['strengths'] as $row)
                        <tr>
                            <td class="small fw-bold"><i class="bi bi-hand-thumbs-up text-success ms-1"></i> {{ $row->subject?->title }}</td>
                            <td><div class="progress" style="height:8px; min-width:70px"><div class="progress-bar bg-success" style="width: {{ max(2, round($row->level)) }}%"></div></div></td>
                            <td class="small">{{ fa_num($row->attempted) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="py-3 text-center text-muted small">داده تسلطی ثبت نشده</td></tr>
                    @endforelse
                    @foreach ($mastery['weaknesses'] as $row)
                        @if (collect($mastery['strengths'])->firstWhere('id', $row->id))
                            @continue
                        @endif
                        <tr>
                            <td class="small fw-bold"><i class="bi bi-hand-thumbs-down text-danger ms-1"></i> {{ $row->subject?->title }}</td>
                            <td><div class="progress" style="height:8px; min-width:70px"><div class="progress-bar bg-danger" style="width: {{ max(2, round($row->level)) }}%"></div></div></td>
                            <td class="small">{{ fa_num($row->attempted) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="small text-muted p-2">سطح تسلط = ۷۰٪ عملکرد آزمون‌های ۹۰ روز اخیر + ۳۰٪ ماندگاری مرور، با افت زمانی.</div>
        </x-card>

        <x-card title="ماندگاری مرور (۳۰ روز اخیر)" icon="bi-arrow-repeat">
            <div class="d-flex align-items-center gap-3">
                <div style="width:120px; height:120px">
                    <canvas id="retentionChart"></canvas>
                </div>
                <div>
                    <div class="fw-bold fs-4 {{ ($retention['retention'] ?? 0) >= 60 ? 'text-success' : 'text-warning' }}">{{ fa_num($retention['retention'] ?? 0) }}٪</div>
                    <div class="small text-muted">مرورهای ثبت‌شده ۳۰ روز اخیر: {{ fa_num($retention['total'] ?? 0) }}</div>
                    <a href="{{ route_if('review.index') }}" class="small">صف مرور امروز <i class="bi bi-arrow-left"></i></a>
                </div>
            </div>
        </x-card>

        <x-card title="پیشنهادهای اصلاحی" icon="bi-lightbulb" bodyClass="py-0">
            <div class="list-group list-group-flush">
                @foreach ($suggestions as $suggestion)
                    <div class="list-group-item d-flex gap-2 small align-items-start">
                        <i class="bi bi-arrow-left-circle text-primary mt-1"></i>
                        <span>{{ $suggestion }}</span>
                    </div>
                @endforeach
            </div>
        </x-card>
    </div>
</div>
