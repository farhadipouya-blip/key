@extends('layouts.app')

@section('title', 'درگاه پرداخت آزمایشی')
@section('page_title', 'درگاه پرداخت آزمایشی')

@php
    $authority = 'A00000' . str_pad((string) $transaction->id, 15, '0', STR_PAD_LEFT);
    $orderNumber = $order?->number ?? ($transaction->meta['order_number'] ?? null);
@endphp

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8 col-xl-7">
        <div class="card border-0 shadow-sm overflow-hidden">
            {{-- سربرگ درگاه --}}
            <div class="bg-warning-subtle border-bottom px-4 py-2 d-flex justify-content-between align-items-center">
                <span class="fw-bold small"><i class="bi bi-info-circle"></i> درگاه پرداخت آزمایشی — هیچ پول واقعی جابجا نمی‌شود</span>
                <span class="badge rounded-pill bg-secondary">{{ strtoupper($transaction->gateway ?: 'mock') }}</span>
            </div>
            <div class="card-header d-flex justify-content-between align-items-center py-3 bg-body">
                <h6 class="mb-0 d-flex align-items-center gap-2">
                    <i class="bi bi-shield-lock text-success"></i>
                    درگاه امن پرداخت
                </h6>
                <div class="small text-muted" id="gatewayTimer">زمان باقی‌مانده: <span class="fw-bold" id="timerValue">۱۵:۰۰</span></div>
            </div>

            <div class="card-body p-4">
                {{-- مبلغ و مشخصات پرداخت --}}
                <div class="text-center mb-4">
                    <div class="small text-muted mb-1">مبلغ قابل پرداخت</div>
                    <div class="fs-3 fw-bold text-primary">{{ money($transaction->amount) }}</div>
                </div>

                <ul class="list-group list-group-flush mb-4">
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">پذیرنده</span>
                        <span class="fw-bold">کلید — سامانه جامع آموزشی</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">شماره سفارش</span>
                        <span class="fw-bold">{{ $orderNumber ?: fa_num($transaction->id) }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">شرح تراکنش</span>
                        <span class="fw-bold">{{ $transaction->meta['description'] ?? ($isWallet ? 'شارژ کیف پول' : 'پرداخت سفارش') }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">شناسه یکتای درگاه (Authority)</span>
                        <span class="fw-bold" style="direction:ltr">{{ $authority }}</span>
                    </li>
                </ul>

                {{-- فرم شبیه‌سازی درگاه --}}
                <form method="GET" action="{{ route('shop.gateway.callback', $transaction) }}" id="gatewayForm">
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <label class="form-label small text-muted" for="mock-pan">شماره کارت (آزمایشی)</label>
                            <input type="text" id="mock-pan" name="mock_pan" class="form-control text-start" placeholder="6104-0000-0000-0000" maxlength="24" value="6104-0000-0000-0000" style="direction:ltr">
                        </div>
                        <div class="col-6 col-md-4">
                            <label class="form-label small text-muted" for="mock-cvv2">CVV2</label>
                            <input type="text" id="mock-cvv2" name="mock_cvv2" class="form-control text-start" maxlength="4" value="123" style="direction:ltr">
                        </div>
                        <div class="col-6 col-md-4">
                            <label class="form-label small text-muted" for="mock-exp">تاریخ انقضا</label>
                            <input type="text" id="mock-exp" name="mock_exp" class="form-control text-start" maxlength="5" value="08/09" style="direction:ltr">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label small text-muted" for="mock-otp">رمز پویا</label>
                            <div class="input-group">
                                <input type="text" id="mock-otp" name="mock_otp" class="form-control text-start" maxlength="8" value="12345" style="direction:ltr">
                                <button type="button" class="btn btn-outline-secondary" onclick="this.previousElementSibling.value = Math.floor(10000 + Math.random()*89999)">ارسال</button>
                            </div>
                        </div>
                    </div>

                    {{-- انتخاب نتیجه پرداخت: مانند صفحه واقعی درگاه --}}
                    <div class="d-grid gap-2">
                        <button type="submit" name="result" value="success" class="btn btn-success btn-lg">
                            <i class="bi bi-shield-check"></i> پرداخت موفق (تأیید و پرداخت)
                        </button>
                        <button type="submit" name="result" value="failed" class="btn btn-outline-danger">
                            <i class="bi bi-x-circle"></i> انصراف / پرداخت ناموفق
                        </button>
                    </div>
                </form>
            </div>

            <div class="card-footer bg-body small text-muted d-flex justify-content-between flex-wrap gap-2">
                <span><i class="bi bi-lock"></i> اتصال شما امن است (شبیه‌سازی SSL)</span>
                <span>مالک تراکنش: {{ auth()->user()->name }}</span>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // شمارش معکوس ۱۵ دقیقه‌ای مانند درگاه واقعی؛ در پایان، تراکنش به‌صورت ناموفق بازگردانده می‌شود
    (function () {
        var total = 15 * 60;
        var el = document.getElementById('timerValue');
        if (!el) return;
        var fa = function (n) { return String(n).replace(/\d/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'[d]; }); };
        var interval = setInterval(function () {
            total--;
            var m = Math.floor(total / 60), s = total % 60;
            el.textContent = fa(m) + ':' + fa(s < 10 ? '0' + s : s);
            if (total <= 0) {
                clearInterval(interval);
                var form = document.getElementById('gatewayForm');
                if (form) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'result';
                    input.value = 'failed';
                    form.appendChild(input);
                    form.submit();
                }
            }
        }, 1000);
    })();
</script>
@endpush
