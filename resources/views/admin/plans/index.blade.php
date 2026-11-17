@extends('layouts.app')

@section('title', 'مدیریت بسته‌های اشتراک')
@section('page_title', 'مدیریت بسته‌های اشتراک')

@section('content')
<x-card title="بسته‌های اشتراک" icon="bi-star"
        actions="<a href='{{ route('admin.plans.create') }}' class='btn btn-primary btn-sm'><i class='bi bi-plus-lg'></i> بسته جدید</a>">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>عنوان</th>
                    <th>قیمت</th>
                    <th>مدت</th>
                    <th>ویژگی‌ها</th>
                    <th>اشتراک‌های فعال</th>
                    <th>ترتیب</th>
                    <th>وضعیت</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($plans as $plan)
                    <tr>
                        <td>
                            <div class="fw-bold">{{ $plan->title }}</div>
                            <div class="small text-muted" style="direction:ltr">{{ $plan->slug }}</div>
                        </td>
                        <td>{{ (float) $plan->price > 0 ? money($plan->price) : 'رایگان' }}</td>
                        <td>{{ fa_num($plan->duration_days) }} روز</td>
                        <td class="small">
                            @if ($plan->features)
                                {{ fa_num(count($plan->features)) }} ویژگی
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>{{ fa_num($plan->subscriptions_count) }}</td>
                        <td>{{ fa_num($plan->sort) }}</td>
                        <td>
                            <x-badge :status="$plan->is_active ? 'active' : 'inactive'"
                                     :labels="['active'=>'فعال','inactive'=>'غیرفعال']"/>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('admin.plans.edit', $plan) }}" class="btn btn-outline-primary btn-sm" title="ویرایش">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @if (! $plan->subscriptions_count)
                                    <form method="POST" action="{{ route('admin.plans.destroy', $plan) }}"
                                          onsubmit="return confirm('آیا از حذف این بسته اطمینان دارید؟')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm" title="حذف">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @else
                                    <button type="button" class="btn btn-outline-danger btn-sm" disabled
                                            title="دارای اشتراک ثبت‌شده — قابل حذف نیست"><i class="bi bi-trash"></i></button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8">
                        <x-empty icon="bi-star" title="بسته‌ای ثبت نشده است"
                                 message="برای شروع فروش اشتراک، یک بسته جدید بسازید."
                                 actionLabel="ساخت بسته جدید" actionUrl="{{ route('admin.plans.create') }}"/>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-card>

@if ($plans->hasPages())
    <div>{{ $plans->links() }}</div>
@endif
@endsection
