<div>
    @if (session('success'))
        <div class="alert alert-success d-flex gap-2 align-items-center">
            <i class="bi bi-check-circle"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger d-flex gap-2 align-items-center">
            <i class="bi bi-x-circle"></i>
            <div>{{ session('error') }}</div>
        </div>
    @endif
    @if (session('info'))
        <div class="alert alert-info d-flex gap-2 align-items-center">
            <i class="bi bi-info-circle"></i>
            <div>{{ session('info') }}</div>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <div class="fw-bold mb-1">لطفاً خطاهای زیر را برطرف کنید:</div>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li class="small">{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
