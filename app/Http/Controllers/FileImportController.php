<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\FileImport;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Subject;
use App\Services\ImportParserService;
use App\Services\XlsxService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * ورود گروهی اطلاعات: بارگذاری فایل (xlsx/csv/docx)، پیش‌نمایش و اعتبارسنجی، ثبت گروهی، نمونه و خروجی اکسل
 */
class FileImportController extends Controller
{
    public function __construct(
        private ImportParserService $parser,
        private XlsxService $xlsx,
    ) {
    }

    public function index()
    {
        $imports = FileImport::query()
            ->with('user:id,name')
            ->orderByDesc('id')
            ->paginate(10);

        $subjects = Subject::query()
            ->whereNotNull('parent_id')
            ->where('is_active', true)
            ->orderBy('parent_id')
            ->orderBy('order')
            ->get();

        $counts = [
            'questions' => Question::count(),
            'imports' => FileImport::count(),
            'success' => FileImport::sum('success_rows'),
        ];

        return view('imports.index', compact('imports', 'subjects', 'counts'));
    }

    /** بارگذاری فایل ورود گروهی */
    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', 'in:questions,historical_ranks,historical'],
            'file' => ['required', 'file', 'max:20480', 'extensions:xlsx,csv,txt,docx'],
            'subject_id' => ['required_if:type,questions', 'nullable', 'integer', 'exists:subjects,id'],
            'preview' => ['nullable', 'in:0,1'],
        ], [
            'type.required' => 'نوع ورود گروهی را انتخاب کنید.',
            'file.required' => 'انتخاب فایل الزامی است.',
            'file.max' => 'حجم فایل باید کمتر از ۲۰ مگابایت باشد.',
            'file.extensions' => 'قالب فایل مجاز نیست — فقط xlsx، csv، txt و docx پذیرفته می‌شود.',
            'subject_id.required_if' => 'برای ورود سؤال، انتخاب درس/مبحث الزامی است.',
        ]);

        $type = $data['type'] === 'historical_ranks' ? 'historical' : $data['type'];
        $file = $request->file('file');

        // اسکن امنیتی: قالب واقعی باید با پسوند بخواند
        try {
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file->getRealPath());
        } catch (Throwable) {
            $mime = '';
        }
        $allowedMimes = [
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
            'csv' => ['text/plain', 'text/csv', 'application/csv', 'application/vnd.ms-excel'],
            'txt' => ['text/plain'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
        ];
        $ext = strtolower($file->getClientOriginalExtension());
        if (! isset($allowedMimes[$ext]) || ! in_array($mime, $allowedMimes[$ext], true)) {
            return back()->with('error', 'محتوای فایل با قالب اعلام‌شده همخوان نیست (' . $mime . ').');
        }
        if ($ext === 'csv' || $ext === 'txt') {
            $head = (string) @file_get_contents($file->getRealPath(), false, null, 0, 8192);
            if (preg_match('/<\?php|<script/i', $head)) {
                return back()->with('error', 'محتوای اسکریپت داخل فایل شناسایی شد و بارگذاری مسدود شد.');
            }
        }

        $stored = $file->storeAs('imports', now()->getTimestamp() . '-' . Str::random(16) . '.' . ($ext ?: 'dat'), 'local');

        $import = FileImport::create([
            'user_id' => $request->user()->id,
            'type' => $type,
            'file_path' => $stored,
            'original_name' => mb_substr($file->getClientOriginalName(), 0, 190),
            'status' => 'pending',
            'stats' => ['subject_id' => $type === 'questions' ? (int) $data['subject_id'] : null],
        ]);

        AuditLog::record($request->user(), 'imports.file.uploaded', $import, [
            'type' => $type,
            'name' => $import->original_name,
        ]);

        $wantsPreview = ($data['preview'] ?? '1') === '1';

        try {
            // اعتبارسنجی اولیه برای شمارش سطرها
            $parsed = $this->parseForImport($import);
        } catch (RuntimeException $e) {
            $import->update(['status' => 'failed', 'errors' => [['row' => 0, 'errors' => [$e->getMessage()]]]]);

            return redirect()->route('imports.index')->with('error', 'فایل قابل پردازش نبود: ' . $e->getMessage());
        }

        $import->update([
            'status' => 'preview',
            'total_rows' => $parsed['total'],
            'error_rows' => count($parsed['invalid']),
            'errors' => array_slice($parsed['invalid'], 0, 100),
        ]);

        if ($wantsPreview) {
            return redirect()->route('imports.preview', $import)->with('info', 'فایل تجزیه شد — نتیجه اعتبارسنجی را بررسی و تأیید کنید.');
        }

        // بدون پیش‌نمایش: اگر هیچ خطایی نبود، مستقیم ثبت شود؛ در غیر این صورت به صفحه پیش‌نمایش
        if (count($parsed['invalid']) === 0 && $parsed['total'] > 0) {
            return $this->commit($request, $import);
        }

        return redirect()->route('imports.preview', $import)->with('error', 'فایل دارای سطر نامعتبر است — بررسی کنید.');
    }

    /** صفحه پیش‌نمایش و اعتبارسنجی سطرها */
    public function preview(Request $request, FileImport $fileImport)
    {
        try {
            $parsed = $this->parseForImport($fileImport);
        } catch (RuntimeException $e) {
            return redirect()->route('imports.index')->with('error', 'فایل قابل پردازش نبود: ' . $e->getMessage());
        }

        if ($fileImport->status !== 'completed') {
            $fileImport->update([
                'status' => 'preview',
                'total_rows' => $parsed['total'],
                'error_rows' => count($parsed['invalid']),
                'errors' => array_slice($parsed['invalid'], 0, 100),
            ]);
        }

        $subject = null;
        if ($fileImport->type === 'questions' && ($fileImport->stats['subject_id'] ?? null)) {
            $subject = Subject::find($fileImport->stats['subject_id']);
        }

        return view('imports.preview', ['import' => $fileImport, 'parsed' => $parsed, 'subject' => $subject]);
    }

    /** ثبت گروهی سطرهای معتبر در پایگاه داده */
    public function commit(Request $request, FileImport $fileImport)
    {
        if ($fileImport->status === 'completed') {
            return redirect()->route('imports.index')->with('error', 'این فایل قبلاً ثبت شده است.');
        }

        try {
            $parsed = $this->parseForImport($fileImport);
        } catch (RuntimeException $e) {
            $fileImport->update(['status' => 'failed', 'errors' => [['row' => 0, 'errors' => [$e->getMessage()]]]]);

            return redirect()->route('imports.index')->with('error', 'فایل قابل پردازش نبود: ' . $e->getMessage());
        }

        $inserted = 0;
        foreach ($parsed['valid'] as $item) {
            if ($fileImport->type === 'questions') {
                $question = Question::create([
                    'subject_id' => $item['subject_id'],
                    'type' => 'multi',
                    'difficulty' => $item['difficulty'],
                    'text' => $item['text'],
                    'explanation' => $item['explanation'],
                    'default_marks' => 1,
                    'is_active' => true,
                    'created_by' => $request->user()->id,
                ]);

                foreach ($item['options'] as $index => $optionText) {
                    QuestionOption::create([
                        'question_id' => $question->id,
                        'order' => $index + 1,
                        'text' => $optionText,
                        'is_correct' => ($index + 1) === $item['correct_index'],
                    ]);
                }
            } else {
                \App\Models\HistoricalResult::create([
                    'user_id' => $item['user_id'],
                    'year' => $item['year'],
                    'exam_name' => $item['exam_name'],
                    'total_percent' => $item['total_percent'],
                    'rank' => $item['rank'],
                    'source' => 'import',
                    'file_import_id' => $fileImport->id,
                ]);
            }
            $inserted++;
        }

        $fileImport->update([
            'status' => 'completed',
            'total_rows' => $parsed['total'],
            'success_rows' => $inserted,
            'error_rows' => count($parsed['invalid']),
            'errors' => array_slice($parsed['invalid'], 0, 100),
            'stats' => array_merge($fileImport->stats ?? [], [
                'subject_id' => $fileImport->stats['subject_id'] ?? null,
                'inserted' => $inserted,
                'committed_at' => now()->toDateTimeString(),
            ]),
            'completed_at' => now(),
        ]);

        AuditLog::record($request->user(), 'imports.committed', $fileImport, [
            'type' => $fileImport->type,
            'inserted' => $inserted,
            'failed' => count($parsed['invalid']),
        ]);

        return redirect()->route('imports.index')->with(
            'success',
            'ورود گروهی انجام شد: ' . fa_num($inserted) . ' رکورد ثبت و ' . fa_num(count($parsed['invalid'])) . ' سطر رد شد.'
        );
    }

    /** خروجی اکسل بانک سؤال با فیلتر */
    public function exportQuestions(Request $request)
    {
        $subjectId = (int) $request->query('subject_id');
        $difficulty = (string) $request->query('difficulty');
        $q = trim((string) $request->query('q'));

        $questions = Question::query()
            ->with(['options', 'subject'])
            ->when($subjectId > 0, fn ($query) => $query->where('subject_id', $subjectId))
            ->when(in_array($difficulty, ['easy', 'medium', 'hard'], true), fn ($query) => $query->where('difficulty', $difficulty))
            ->when($q !== '', fn ($query) => $query->where('text', 'like', "%{$q}%"))
            ->orderBy('subject_id')
            ->orderBy('id')
            ->limit(5000)
            ->get();

        $rows = [['متن سؤال', 'گزینه ۱', 'گزینه ۲', 'گزینه ۳', 'گزینه ۴', 'پاسخ صحیح', 'توضیح', 'دشواری', 'درس/مبحث']];
        foreach ($questions as $question) {
            $options = $question->options->sortBy('order')->values();
            $correctIndex = 0;
            foreach ($options as $i => $option) {
                if ($option->is_correct) {
                    $correctIndex = $i + 1;
                }
            }
            $difficultyTitles = ['easy' => 'آسان', 'medium' => 'متوسط', 'hard' => 'سخت'];
            $rows[] = [
                $question->text,
                $options[0]?->text ?? '',
                $options[1]?->text ?? '',
                $options[2]?->text ?? '',
                $options[3]?->text ?? '',
                $correctIndex ?: '',
                (string) $question->explanation,
                $difficultyTitles[$question->difficulty] ?? 'متوسط',
                $question->subject?->fullTitle(),
            ];
        }

        $filename = 'questions-export-' . now()->getTimestamp() . '.xlsx';
        $path = storage_path('app/exports/' . $filename);
        $this->xlsx->writeXlsx($rows, $path);

        AuditLog::record($request->user(), 'imports.questions.exported', null, ['count' => $questions->count()]);

        return response()->download($path, 'questions-export.xlsx')->deleteFileAfterSend(true);
    }

    /** دانلود فایل نمونه برای هر نوع ورود */
    public function sample(string $type)
    {
        if (! in_array($type, ['questions', 'historical'], true) && ! in_array($type, ['historical_ranks'], true)) {
            abort(404);
        }
        if ($type === 'historical_ranks') {
            $type = 'historical';
        }

        $dir = storage_path('app/imports/samples');
        $path = $dir . '/' . $type . '-sample.xlsx';

        if (! is_file($path)) {
            if ($type === 'questions') {
                $rows = [
                    ['متن سؤال', 'گزینه ۱', 'گزینه ۲', 'گزینه ۳', 'گزینه ۴', 'پاسخ صحیح', 'توضیح', 'دشواری'],
                    ['دامنه تابع f(x) = √(x−2) کدام است؟', 'x ≥ ۲', 'x > ۲', 'x ≤ ۲', 'همه اعداد حقیقی', '۱', 'شرط رادیکال: x−۲ ≥ صفر، پس x ≥ ۲', 'آسان'],
                    ['واحد SI سرعت چیست؟', 'نیوتن', 'متر بر ثانیه', 'ژول', 'وات', '۲', 'سرعت بر حسب متر بر ثانیه اندازه‌گیری می‌شود.', 'آسان'],
                    ['مشتق تابع x² نسبت به x کدام است؟', 'x', '2x', 'x²/2', '۲', '۲', 'با قاعده توان: d/dx(x²) = 2x', 'متوسط'],
                    ['ساختار لایه‌ای اتم در مدل بور چگونه است؟', 'الکترون‌ها پراکنده‌اند', 'الکترون‌ها در مدارهای معین می‌چرخند', 'پروتون در مدار می‌چرخد', 'هسته متحرک است', '۲', 'در مدل بور الکترون‌ها در ترازهای انرژی معین می‌گردند.', 'سخت'],
                ];
            } else {
                $rows = [
                    ['ایمیل کاربر', 'عنوان آزمون', 'سال', 'رتبه', 'درصد کل'],
                    ['student@key.test', 'آزمون آزمایشی سراسری', '1402', '15420', '72.5'],
                    ['student@key.test', 'آزمون جامع نوبت اول', '1401', '23150', '65'],
                ];
            }

            if (! is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            $this->xlsx->writeXlsx($rows, $path);
        }

        return response()->download($path, ($type === 'questions' ? 'questions' : 'historical') . '-sample.xlsx');
    }

    // ------------------------------------------------------------------

    /** خواندن و اعتبارسنجی فایل یک Import بر اساس نوع */
    private function parseForImport(FileImport $fileImport): array
    {
        if (! $fileImport->file_path || ! $fileImport->type || ! Storage::disk('local')->exists($fileImport->file_path)) {
            throw new RuntimeException('فایل این درخواست یافت نشد؛ لطفاً فایل را دوباره بارگذاری کنید.');
        }

        $absolutePath = Storage::disk('local')->path($fileImport->file_path);
        $rows = $this->parser->read($absolutePath);

        if ($rows === []) {
            throw new RuntimeException('فایل هیچ سطر داده‌ای ندارد.');
        }

        return $fileImport->type === 'questions'
            ? $this->parser->parseQuestions($rows, (int) ($fileImport->stats['subject_id'] ?? 0))
            : $this->parser->parseHistorical($rows);
    }
}
