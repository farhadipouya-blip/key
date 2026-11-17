@extends('layouts.app')

@section('title', 'نقش‌ها و مجوزها')
@section('page_title', 'مدیریت نقش‌ها و مجوزها')

@php
    $groupedPermissions = $permissions->groupBy('group');
    $groupTitles = [
        'آزمون' => 'آزمون و بانک سؤال',
        'یادگیری' => 'یادگیری',
        'آکادمی' => 'آکادمی و دوره‌ها',
        'تجارت' => 'فروشگاه و اشتراک',
        'ارتباطات' => 'ارتباطات',
        'مشاوره' => 'مشاوره',
        'مدیریت' => 'مدیریت سامانه',
        'متفرقه' => 'متفرقه',
    ];
@endphp

@section('content')
<div class="row g-3">
    @foreach ($roles as $role)
        <div class="col-12 col-xl-6">
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center py-3">
                    <h6 class="mb-0 d-flex align-items-center gap-2 flex-wrap">
                        <i class="bi bi-shield-lock text-primary"></i>
                        <span>{{ $role->name }}</span>
                        <code class="small text-muted" dir="ltr">{{ $role->slug }}</code>
                        <span class="badge rounded-pill badge-soft-primary">{{ fa_num($role->users_count) }} کاربر</span>
                        @if ($role->slug === 'superadmin')
                            <span class="badge rounded-pill badge-soft-warning">دسترسی کامل (بای‌پس)</span>
                        @endif
                    </h6>
                </div>
                <div class="card-body">
                    @if ($role->description)
                        <p class="small text-muted mb-2">{{ $role->description }}</p>
                    @endif

                    <form method="POST" action="{{ route('admin.roles.update', $role) }}" data-role-form="{{ $role->id }}">
                        @csrf
                        @php $rolePermIds = $role->permissions->pluck('id')->all(); @endphp
                        <div class="row g-2">
                            @foreach ($groupedPermissions as $group => $perms)
                                <div class="col-12 col-md-6">
                                    <div class="border rounded-3 p-2 h-100">
                                        <div class="small fw-bold text-muted mb-2">{{ $groupTitles[$group] ?? $group }}</div>
                                        @foreach ($perms as $perm)
                                            <div class="form-check" title="{{ $perm->slug }}">
                                                <input class="form-check-input perm-cb" type="checkbox"
                                                       name="permission_ids[]" value="{{ $perm->id }}"
                                                       id="perm-{{ $role->id }}-{{ $perm->id }}"
                                                       @if (in_array($perm->id, $rolePermIds)) checked @endif>
                                                <label class="form-check-label small" for="perm-{{ $role->id }}-{{ $perm->id }}">{{ $perm->name }}</label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <span class="small text-muted perm-count" data-count-for="{{ $role->id }}">
                                {{ fa_num(count($rolePermIds)) }} مجوز فعال
                            </span>
                            <button class="btn btn-sm btn-primary">
                                <i class="bi bi-check-lg"></i> ذخیره مجوزهای نقش
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-3">
    <div class="col-12">
        <x-card title="شرح مجوزها" icon="bi-list-check" bodyClass="py-0">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>مجوز</th><th>شناسه</th><th>گروه</th><th>دارندگان</th></tr></thead>
                    <tbody>
                    @foreach ($permissions as $perm)
                        @php $holders = $roles->filter(fn ($r) => $r->permissions->contains('id', $perm->id))->pluck('name'); @endphp
                        <tr>
                            <td class="small fw-bold">{{ $perm->name }}</td>
                            <td class="small" dir="ltr"><code>{{ $perm->slug }}</code></td>
                            <td class="small text-muted">{{ $groupTitles[$perm->group] ?? $perm->group }}</td>
                            <td class="small">{{ $holders->implode('، ') ?: '—' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.perm-cb').forEach(function (cb) {
        cb.addEventListener('change', function () {
            const form = cb.closest('form');
            const roleId = form.getAttribute('data-role-form');
            const count = form.querySelectorAll('.perm-cb:checked').length;
            const span = document.querySelector('[data-count-for="' + roleId + '"]');
            if (span) span.textContent = count.toLocaleString('fa-IR') + ' مجوز فعال';
        });
    });
</script>
@endpush
