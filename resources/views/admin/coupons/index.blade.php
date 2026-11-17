@extends('layouts.app')

@section('title', 'مدیریت کوپن‌ها')
@section('page_title', 'مدیریت کوپن‌ها')

@section('content')
<x-card title="کوپن‌های تخفیف" icon="bi-ticket-perforated"
        actions="<a href='{{ route('admin.coupons.create') }}' class='btn btn-primary btn-sm'><i class='bi bi-plus-lg'></i> کوپن جدید</a>">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>کد</th>
                    <th>نوع تخفیف</th>
                    <th>مقدار</th>
                    <th>حداقل مبلغ</th>
                    <th>مصرف</th>
                    <th>انقضا</th>
                    <th>وضعیت</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($coupons as $coupon)
                    <tr>
                        <td class="fw-bold" style="direction:ltr">{{ $coupon->code }}</td>
                        <td>
                            <x-badge :status="$coupon->type" :labels="['percent'=>'درصدی','fixed'=>'مبلغ ثابت']"/>
                        </td>
                        <td>
                            @if ($coupon->type === 'percent')
                                {{ fa_num($coupon->value) }}٪
                            @else
                                {{ money($coupon->value) }}
                            @endif
                        </td>
                        <td>{{ $coupon->min_amount ? money($coupon->min_amount) : '—' }}</td>
                        <td>
                            {{ fa_num($coupon->used_count) }}
                            @if ($coupon->max_uses)
                                <span class="text-muted">از {{ fa_num($coupon->max_uses) }}</span>
                            @endif
                        </td>
                        <td class="small">{{ $coupon->expires_at ? fa_date($coupon->expires_at) : 'بدون انقضا' }}</td>
                        <td>
                            <x-badge :status="$coupon->is_active ? 'active' : 'inactive'"
                                     :labels="['active'=>'فعال','inactive'=>'غیرفعال']"/>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('admin.coupons.edit', $coupon) }}" class="btn btn-outline-primary btn-sm" title="ویرایش">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @if ($coupon->used_count == 0)
                                    <form method="POST" action="{{ route('admin.coupons.destroy', $coupon) }}"
                                          onsubmit="return confirm('آیا از حذف این کوپن اطمینان دارید؟')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm" title="حذف">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @else
                                    <button type="button" class="btn btn-outline-danger btn-sm" disabled
                                            title="قبلاً استفاده شده — قابل حذف نیست"><i class="bi bi-trash"></i></button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8">
                        <x-empty icon="bi-ticket-perforated" title="کوپنی ثبت نشده است"
                                 message="برای ایجاد کدهای تخفیف فروشگاه، یک کوپن جدید بسازید."
                                 actionLabel="ساخت کوپن جدید" actionUrl="{{ route('admin.coupons.create') }}"/>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-card>

@if ($coupons->hasPages())
    <div>{{ $coupons->links() }}</div>
@endif
@endsection
