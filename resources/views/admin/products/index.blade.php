@extends('layouts.app')

@section('title', 'مدیریت محصولات')
@section('page_title', 'مدیریت محصولات')

@section('content')
<x-card title="محصولات آموزشی" icon="bi-box-seam"
        actions="<a href='{{ route('admin.products.create') }}' class='btn btn-primary btn-sm'><i class='bi bi-plus-lg'></i> محصول جدید</a>">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>عنوان</th>
                    <th>نامک</th>
                    <th>قیمت</th>
                    <th>وضعیت انتشار</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    <tr>
                        <td>
                            <div class="fw-bold">{{ $product->title }}</div>
                            @if ($product->description)
                                <div class="small text-muted">{{ \Illuminate\Support\Str::limit(strip_tags($product->description), 70) }}</div>
                            @endif
                        </td>
                        <td class="small text-muted" style="direction:ltr">{{ $product->slug }}</td>
                        <td>{{ (float) $product->price > 0 ? money($product->price) : 'رایگان' }}</td>
                        <td>
                            <x-badge :status="$product->is_published ? 'published' : 'draft'"
                                     :labels="['published'=>'منتشرشده','draft'=>'پیش‌نویس']"/>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-outline-primary btn-sm" title="ویرایش">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="{{ route('admin.products.destroy', $product) }}"
                                      onsubmit="return confirm('آیا از حذف این محصول اطمینان دارید؟')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm" title="حذف">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5">
                        <x-empty icon="bi-box-seam" title="محصولی ثبت نشده است"
                                 message="برای فروش محصولات آموزشی، یک محصول جدید بسازید."
                                 actionLabel="ساخت محصول جدید" actionUrl="{{ route('admin.products.create') }}"/>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-card>

@if ($products->hasPages())
    <div>{{ $products->links() }}</div>
@endif
@endsection
