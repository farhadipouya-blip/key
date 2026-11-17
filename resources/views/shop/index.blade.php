@extends('layouts.app')

@section('title', 'فروشگاه و اشتراک')
@section('page_title', 'فروشگاه و اشتراک')

@section('content')
@php
    $cartCount = count(session('cart', []));
    $balance = $user->walletBalance();
    $hasSubscription = $user->hasActiveSubscription();
@endphp

{{-- نوار بالای فروشگاه --}}
<div class="row g-3 mb-4">
    <div class="col-12 col-md-6 col-lg-4">
        <x-stat icon="bi-wallet2" label="موجودی کیف پول" value="{{ money($balance) }}" color="{{ $balance > 0 ? 'success' : 'secondary' }}" url="{{ route('shop.wallet') }}"/>
    </div>
    <div class="col-12 col-md-6 col-lg-4">
        <x-stat icon="bi-cart3" label="آیتم‌های سبد خرید" value="{{ fa_num($cartCount) }} مورد" color="primary" url="{{ route('shop.cart') }}"/>
    </div>
    <div class="col-12 col-md-6 col-lg-4">
        <x-stat icon="bi-star" label="وضعیت اشتراک" value="{{ $hasSubscription ? 'فعال' : 'بدون اشتراک' }}" color="{{ $hasSubscription ? 'info' : 'warning' }}" url="{{ route('shop.index') }}"/>
    </div>
</div>

{{-- بسته‌های اشتراک --}}
<x-card title="اشتراک‌ها" icon="bi-star">
    <div class="row g-3">
        @forelse ($plans as $plan)
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 {{ $plan->badge ? 'border-primary' : '' }}">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h6 class="fw-bold mb-0">{{ $plan->title }}</h6>
                            @if ($plan->badge)
                                <span class="badge rounded-pill badge-soft-warning">{{ $plan->badge }}</span>
                            @endif
                        </div>
                        <div class="mb-2">
                            @if ((float) $plan->price > 0)
                                <span class="fw-bold text-primary fs-5">{{ money($plan->price) }}</span>
                            @else
                                <span class="fw-bold text-success fs-5">رایگان</span>
                            @endif
                        </div>
                        <div class="small text-muted mb-3">
                            <i class="bi bi-calendar3"></i> مدت اشتراک: {{ fa_num($plan->duration_days) }} روز
                        </div>
                        @if ($plan->description)
                            <p class="small text-muted mb-2">{{ \Illuminate\Support\Str::limit($plan->description, 90) }}</p>
                        @endif
                        @if ($plan->features)
                            <ul class="list-unstyled small mb-3">
                                @foreach (array_slice($plan->features, 0, 5) as $feature)
                                    <li class="mb-1"><i class="bi bi-check2-circle text-success"></i> {{ $feature }}</li>
                                @endforeach
                            </ul>
                        @endif
                        <a href="{{ route('shop.subscribe', $plan) }}" class="btn btn-primary btn-sm mt-auto w-100">
                            <i class="bi bi-lightning-charge"></i> خرید و فعال‌سازی
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <x-empty icon="bi-star" title="بسته اشتراکی ثبت نشده است" message="در حال حاضر بسته اشتراکی برای نمایش وجود ندارد."/>
            </div>
        @endforelse
    </div>
</x-card>

{{-- دوره‌های ویژه (پولی) --}}
<x-card title="دوره‌های ویژه" icon="bi-mortarboard">
    <div class="row g-3">
        @forelse ($paidCourses as $course)
            <div class="col-md-6 col-lg-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h6 class="fw-bold mb-2">{{ $course->title }}</h6>
                        @if ($course->teacher)
                            <div class="small text-muted mb-2"><i class="bi bi-person"></i> مدرس: {{ $course->teacher->name }}</div>
                        @endif
                        <div class="d-flex flex-wrap gap-2 mb-3 small text-muted">
                            @if ($course->duration_hours)
                                <span><i class="bi bi-clock"></i> {{ duration_human($course->duration_hours * 60) }}</span>
                            @endif
                            <span><i class="bi bi-play-circle"></i> {{ fa_num($course->lessons()->count()) }} درس</span>
                        </div>
                        <div class="fw-bold text-primary mb-3">{{ money($course->price) }}</div>
                        <form method="POST" action="{{ route('shop.add') }}">
                            @csrf
                            <input type="hidden" name="purchasable_type" value="course">
                            <input type="hidden" name="purchasable_id" value="{{ $course->id }}">
                            <button type="submit" class="btn btn-outline-primary btn-sm w-100">
                                <i class="bi bi-cart-plus"></i> افزودن به سبد خرید
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <x-empty icon="bi-mortarboard" title="دوره ویژه‌ای یافت نشد" message="در حال حاضر دوره پولی برای فروش ثبت نشده است."/>
            </div>
        @endforelse
    </div>
</x-card>

{{-- دوره‌های رایگان --}}
<x-card title="دوره‌های رایگان" icon="bi-gift">
    <div class="row g-3">
        @forelse ($freeCourses as $course)
            <div class="col-md-6 col-lg-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h6 class="fw-bold mb-0">{{ $course->title }}</h6>
                            <span class="badge rounded-pill badge-soft-success">رایگان</span>
                        </div>
                        @if ($course->teacher)
                            <div class="small text-muted mb-2"><i class="bi bi-person"></i> مدرس: {{ $course->teacher->name }}</div>
                        @endif
                        <div class="small text-muted mb-3">
                            <i class="bi bi-play-circle"></i> {{ fa_num($course->lessons()->count()) }} درس
                            @if ($course->duration_hours)
                                — <i class="bi bi-clock"></i> {{ duration_human($course->duration_hours * 60) }}
                            @endif
                        </div>
                        <form method="POST" action="{{ route('shop.add') }}">
                            @csrf
                            <input type="hidden" name="purchasable_type" value="course">
                            <input type="hidden" name="purchasable_id" value="{{ $course->id }}">
                            <button type="submit" class="btn btn-success btn-sm w-100">
                                <i class="bi bi-person-plus"></i> ثبت‌نام رایگان
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <x-empty icon="bi-gift" title="دوره رایگانی یافت نشد" message="در حال حاضر دوره رایگان ثبت نشده است."/>
            </div>
        @endforelse
    </div>
</x-card>

{{-- محصولات --}}
<x-card title="محصولات" icon="bi-box-seam">
    <div class="row g-3">
        @forelse ($products as $product)
            <div class="col-md-6 col-lg-3">
                <div class="card h-100">
                    <div class="card-body">
                        <h6 class="fw-bold mb-2">{{ $product->title }}</h6>
                        @if ($product->description)
                            <p class="small text-muted mb-3">{{ \Illuminate\Support\Str::limit(strip_tags($product->description), 80) }}</p>
                        @endif
                        <div class="fw-bold text-primary mb-3">{{ money($product->price) }}</div>
                        <form method="POST" action="{{ route('shop.add') }}">
                            @csrf
                            <input type="hidden" name="purchasable_type" value="product">
                            <input type="hidden" name="purchasable_id" value="{{ $product->id }}">
                            <button type="submit" class="btn btn-outline-primary btn-sm w-100">
                                <i class="bi bi-cart-plus"></i> افزودن به سبد خرید
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <x-empty icon="bi-box-seam" title="محصولی یافت نشد" message="در حال حاضر محصولی برای فروش ثبت نشده است."/>
            </div>
        @endforelse
    </div>
</x-card>
@endsection
