@extends('layouts.app')

@section('title', 'خلاصه‌های عمومی')
@section('page_title', 'مدیریت خلاصه‌های عمومی')

@section('content')
<div class="row g-3">
    <div class="col-lg-8">
        <x-card title="خلاصه‌های عمومی منتشرشده" icon="bi-file-earmark-text" bodyClass="py-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>عنوان</th><th>دسته</th><th>درس</th><th>تاریخ</th><th></th></tr></thead>
                    <tbody>
                    @forelse ($summaries as $summary)
                        <tr>
                            <td class="small fw-bold">{{ $summary->title }}</td>
                            <td class="small">{{ $summary->category ?? '—' }}</td>
                            <td class="small">{{ $summary->subject?->fullTitle() ?? '—' }}</td>
                            <td class="small text-muted">{{ fa_date($summary->created_at) }}</td>
                            <td>
                                <a href="{{ route('summaries.show', $summary) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                                <form method="POST" action="{{ route('admin.summaries.destroy', $summary) }}" class="d-inline"
                                      onsubmit="return confirm('این خلاصه عمومی حذف شود؟')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-4"><x-empty icon="bi-inbox" title="خلاصه عمومی منتشر نشده است"/></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
        {{ $summaries->links() }}
    </div>

    <div class="col-lg-4">
        <x-card title="انتشار خلاصه عمومی جدید" icon="bi-plus-circle">
            <form method="POST" action="{{ route('admin.summaries.store') }}">
                @csrf
                <x-input name="title" label="عنوان" required/>
                <x-textarea name="content" label="متن خلاصه" rows="7" required/>
                <x-select name="category" label="دسته" :options="$categories"/>
                <x-select name="subject_id" label="درس/مبحث" :options="$subjects->mapWithKeys(fn($s) => [$s->id => $s->fullTitle()])"/>
                <x-input name="tags" label="برچسب‌ها" placeholder="با ویرگول جدا کنید"/>
                <button class="btn btn-primary w-100"><i class="bi bi-send"></i> انتشار عمومی</button>
            </form>
        </x-card>

        <x-card bodyClass="py-3">
            <form method="GET" action="{{ route('admin.summaries.index') }}">
                <label class="form-label small">جست‌وجوی عنوان</label>
                <div class="input-group input-group-sm">
                    <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="عنوان...">
                    <button class="btn btn-outline-primary"><i class="bi bi-search"></i></button>
                </div>
            </form>
        </x-card>
    </div>
</div>
@endsection
