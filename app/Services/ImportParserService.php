<?php

namespace App\Services;

use App\Models\User;
use RuntimeException;

/**
 * تجزیه و اعتبارسنجی سطرهای ورود گروهی: سؤالات و کارنامه‌های تاریخی
 * ستون‌ها به‌صورت سرآیند فارسی یا انگلیسی شناسایی می‌شوند؛ اگر سطر اول سرآیند نباشد، ترتیب ثابت فرض می‌شود.
 */
class ImportParserService
{
    public const DIFFICULTY_MAP = [
        'easy' => 'easy', 'آسان' => 'easy',
        'medium' => 'medium', 'متوسط' => 'medium', 'متوسطه' => 'medium',
        'hard' => 'hard', 'سخت' => 'hard', 'دشوار' => 'hard',
    ];

    /**
     * خواندن فایل ورودی بر اساس پسوند (xlsx/csv/docx/txt)
     *
     * @return array<int, array<int, string>>
     */
    public function read(string $absolutePath): array
    {
        $xlsx = new XlsxService();
        $ext = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));

        $rows = match ($ext) {
            'xlsx' => $xlsx->readXlsx($absolutePath),
            'csv', 'txt' => $xlsx->readCsv($absolutePath),
            'docx' => $xlsx->readDocx($absolutePath),
            default => throw new RuntimeException('قالب فایل پشتیبانی نمی‌شود (فقط xlsx، csv، docx، txt).'),
        };

        // حذف سطرهای کاملاً خالی
        return array_values(array_filter($rows, fn ($row) => array_filter($row, fn ($c) => trim((string) $c) !== '') !== []));
    }

    /**
     * تجزیه و اعتبارسنجی سؤالات
     *
     * @param array<int, array<int, string>> $rows
     * @return array{valid: array<int, array>, invalid: array<int, array>, total: int}
     */
    public function parseQuestions(array $rows, int $subjectId): array
    {
        $rows = $this->stripHeader($rows, ['متن سؤال', 'متن سوال', 'text', 'سؤال']);
        $valid = [];
        $invalid = [];

        foreach ($rows as $i => $row) {
            $rowNumber = $i + 2; // با احتساب سطر سرآیند
            $text = trim((string) ($row[0] ?? ''));
            $option1 = trim((string) ($row[1] ?? ''));
            $option2 = trim((string) ($row[2] ?? ''));
            $option3 = trim((string) ($row[3] ?? ''));
            $option4 = trim((string) ($row[4] ?? ''));
            $correctRaw = trim((string) ($row[5] ?? ''));
            $explanation = trim((string) ($row[6] ?? ''));
            $difficultyRaw = trim((string) ($row[7] ?? ''));

            $errors = [];
            if ($text === '') {
                $errors[] = 'متن سؤال خالی است';
            }
            foreach (['گزینه ۱' => $option1, 'گزینه ۲' => $option2, 'گزینه ۳' => $option3, 'گزینه ۴' => $option4] as $label => $option) {
                if ($option === '') {
                    $errors[] = $label . ' خالی است';
                }
            }
            $correct = $this->normalizeCorrectIndex($correctRaw);
            if ($correct === null) {
                $errors[] = 'پاسخ صحیح باید عدد ۱ تا ۴ باشد («' . $correctRaw . '»)';
            }
            $difficulty = self::DIFFICULTY_MAP[$difficultyRaw] ?? 'medium';

            if ($errors !== []) {
                $invalid[] = ['row' => $rowNumber, 'text' => mb_substr($text, 0, 80), 'errors' => $errors];
                continue;
            }

            $valid[] = [
                'row' => $rowNumber,
                'subject_id' => $subjectId,
                'text' => $text,
                'options' => [$option1, $option2, $option3, $option4],
                'correct_index' => $correct,
                'explanation' => $explanation ?: null,
                'difficulty' => $difficulty,
            ];
        }

        return ['valid' => $valid, 'invalid' => $invalid, 'total' => count($rows)];
    }

    /**
     * تجزیه و اعتبارسنجی کارنامه‌های تاریخی
     *
     * @param array<int, array<int, string>> $rows
     * @return array{valid: array<int, array>, invalid: array<int, array>, total: int}
     */
    public function parseHistorical(array $rows): array
    {
        $rows = $this->stripHeader($rows, ['ایمیل', 'email', 'ایمیل کاربر']);
        $valid = [];
        $invalid = [];

        $userEmails = User::query()->pluck('id', 'email')->all();

        foreach ($rows as $i => $row) {
            $rowNumber = $i + 2;
            $email = trim(mb_strtolower((string) ($row[0] ?? '')));
            $examName = trim((string) ($row[1] ?? ''));
            $yearRaw = trim((string) ($row[2] ?? ''));
            $rankRaw = trim((string) ($row[3] ?? ''));
            $percentRaw = trim((string) ($row[4] ?? ''));

            $errors = [];
            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'ایمیل کاربر نامعتبر است («' . $email . '»)';
            } elseif (! isset($userEmails[$email])) {
                $errors[] = 'کاربری با این ایمیل یافت نشد';
            }

            $year = (int) str_replace(['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'], ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'], $yearRaw);
            if ($yearRaw === '' || $year < 1370 || $year > 1450) {
                $errors[] = 'سال نامعتبر است (باید بین ۱۳۷۰ تا ۱۴۵۰ باشد)';
            }

            $rank = null;
            if ($rankRaw !== '') {
                $rank = (int) str_replace(['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'], ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'], $rankRaw);
                if ($rank <= 0) {
                    $errors[] = 'رتبه باید عددی مثبت باشد';
                }
            }

            $percent = null;
            if ($percentRaw !== '') {
                $percent = (float) str_replace(['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹', '٫'], ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '.'], $percentRaw);
                if ($percent < 0 || $percent > 100) {
                    $errors[] = 'درصد کل باید بین ۰ تا ۱۰۰ باشد';
                }
            }

            if ($errors !== []) {
                $invalid[] = ['row' => $rowNumber, 'text' => $email ?: '—', 'errors' => $errors];
                continue;
            }

            $valid[] = [
                'row' => $rowNumber,
                'user_id' => $userEmails[$email],
                'email' => $email,
                'exam_name' => $examName ?: 'کارنامه ' . fa_num($year),
                'year' => $year,
                'rank' => $rank,
                'total_percent' => $percent,
            ];
        }

        return ['valid' => $valid, 'invalid' => $invalid, 'total' => count($rows)];
    }

    /** حذف سطر سرآیند در صورت تشخیص */
    private function stripHeader(array $rows, array $needles): array
    {
        if ($rows === []) {
            return $rows;
        }

        $first = implode('|', array_slice(array_map(fn ($c) => trim((string) $c), $rows[0]), 0, 8));
        foreach ($needles as $needle) {
            if ($first !== '' && mb_stripos($first, $needle) !== false) {
                return array_slice($rows, 1);
            }
        }

        return $rows;
    }

    private function normalizeCorrectIndex(string $raw): ?int
    {
        $normalized = trim($this->faDigitsToAscii($raw));

        if (in_array($normalized, ['1', '2', '3', '4'], true)) {
            return (int) $normalized;
        }

        return null;
    }

    /** تبدیل ارقام فارسی/عربی به لاتین */
    private function faDigitsToAscii(string $text): string
    {
        return str_replace(
            ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹', '٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩', '٫'],
            ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '.'],
            $text
        );
    }
}
