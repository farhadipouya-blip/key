@extends('layouts.app')

@section('title', 'کیف پول')
@section('page_title', 'کیف پول')

@section('content')
<div class="row g-3 mb-3">
    <div class="col-12 col-md-6 col-lg-4">
        <x-stat icon="bi-wallet2" label="موجودی فعلی کیف پول" value="{{ money($balance) }}" color="{{ $balance > 0 ? 'success' : 'secondary' }}"/>
    </div>
    <div class="col-12 col-md-6 col-lg-4">
        <x-stat icon="bi-arrow-down-circle" label="مجموع واریزها و کش‌بک‌ها" value="{{ money($transactions->where('amount', '>', 0)->sum('amount')) }}" color="primary"/>
    </div>
    <div class="col-12 col-md-6 col-lg-4">
        <x-stat icon="bi-arrow-up-circle" label="مجموع برداشت‌ها" value="{{ money(abs($transactions->where('amount', '<', 0)->sum('amount'))) }}" color="danger"/>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <x-card title="شارژ کیف پول" icon="bi-plus-circle">
            <form method="POST" action="{{ route('shop.wallet.charge') }}">
                @csrf
                <x-input name="amount" type="number" label="مبلغ شارژ (تومان)" required money
                         value="{{ old('amount') }}" placeholder="مثلاً 500000"
                         help="حداقل مبلغ شارژ ۱۰,۰۰۰ تومان و حداکثر ۵۰۰,۰۰۰,۰۰۰ تومان است."/>
                <div class="d-flex gap-2 mb-3" id="wallet-quick-amounts">
                    <button type="button" class="btn btn-outline-primary btn-sm flex-fill" data-amount="100000">{{ fa_num(100000) }}</button>
                    <button type="button" class="btn btn-outline-primary btn-sm flex-fill" data-amount="500000">{{ fa_num(500000) }}</button>
                    <button type="button" class="btn btn-outline-primary btn-sm flex-fill" data-amount="1000000">{{ fa_num(1000000) }}</button>
                </div>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-credit-card"></i> پرداخت و شارژ کیف پول
                </button>
            </form>
            <div class="form-text mt-2">
                پس از ثبت به درگاه پرداخت آزمایشی هدایت می‌شوید؛ اعتبار کیف پول در خریدهای بعدی قابل استفاده است.
            </div>
        </x-card>
    </div>

    <div class="col-lg-8">
        <x-card title="تراکنش‌های کیف پول" icon="bi-list-ul">
            @if ($transactions->isEmpty())
                <x-empty icon="bi-wallet2" title="تراکنشی ثبت نشده است"
                         message="پس از شارژ کیف پول یا اولین خرید، تراکنش‌ها اینجا نمایش داده می‌شوند."
                         actionLabel="شارژ کیف پول" actionUrl="{{ route('shop.wallet') }}"/>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>شرح</th>
                                <th>نوع</th>
                                <th>مبلغ</th>
                                <th>موجودی پس از تراکنش</th>
                                <th>تاریخ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($transactions as $t)
                                <tr>
                                    <td class="small">{{ $t->description ?: '—' }}</td>
                                    <td>
                                        <x-badge :status="$t->type"
                                                 :labels="['deposit'=>'واریز','purchase'=>'خرید','cashback'=>'کش‌بک','refund'=>'بازگشت وجه','manual'=>'دستی']"/>
                                    </td>
                                    <td class="fw-bold {{ $t->amount >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $t->amount >= 0 ? '+ ' : '− ' }}{{ money(abs((float) $t->amount)) }}
                                    </td>
                                    <td>{{ money($t->balance_after) }}</td>
                                    <td class="small text-muted">{{ fa_datetime($t->created_at) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('#wallet-quick-amounts [data-amount]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var field = document.getElementById('fld-amount');
            if (field) field.value = btn.dataset.amount;
        });
    });
</script>
@endpush
