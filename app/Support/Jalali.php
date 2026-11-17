<?php

namespace App\Support;

/**
 * تبدیل تاریخ میلادی به جلالی (شمسی) و قالب‌بندی فارسی
 * الگوریتم استاندارد تبدیل جلالی-میلادی
 */
class Jalali
{
    public const MONTHS = [
        'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور',
        'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند',
    ];

    public const WEEKDAYS = [
        'شنبه', 'یک‌شنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنج‌شنبه', 'جمعه',
    ];

    /**
     * تبدیل تاریخ میلادی به آرایه جلالی [سال، ماه، روز]
     */
    public static function toJalali(int $gy, int $gm, int $gd): array
    {
        $g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];

        $gy2 = $gm > 2 ? $gy + 1 : $gy;
        $days = 355666 + (365 * $gy) + ((int) (($gy2 + 3) / 4)) - ((int) (($gy2 + 99) / 100))
            + ((int) (($gy2 + 399) / 400)) + $gd + $g_d_m[$gm - 1];
        $jy = -1595 + (33 * ((int) ($days / 12053)));
        $days %= 12053;
        $jy += 4 * ((int) ($days / 1461));
        $days %= 1461;

        if ($days > 365) {
            $jy += (int) (($days - 1) / 365);
            $days = ($days - 1) % 365;
        }

        if ($days < 186) {
            $jm = 1 + (int) ($days / 31);
            $jd = 1 + ($days % 31);
        } else {
            $jm = 7 + (int) (($days - 186) / 30);
            $jd = 1 + (($days - 186) % 30);
        }

        return [$jy, $jm, $jd];
    }

    /**
     * تبدیل تاریخ جلالی به میلادی
     */
    public static function toGregorian(int $jy, int $jm, int $jd): array
    {
        $jy += 1595;
        $days = -355668 + (365 * $jy) + (((int) ($jy / 33)) * 8) + (((int) ((($jy % 33) + 3) / 4))) + $jd
            + (($jm < 7) ? ($jm - 1) * 31 : (($jm - 7) * 30) + 186);

        $gy = 400 * ((int) ($days / 146097));
        $days %= 146097;

        if ($days > 36524) {
            $gy += 100 * ((int) (--$days / 36524));
            $days %= 36524;
            if ($days >= 365) {
                $days++;
            }
        }

        $gy += 4 * ((int) ($days / 1461));
        $days %= 1461;

        if ($days > 365) {
            $gy += (int) (($days - 1) / 365);
            $days = ($days - 1) % 365;
        }

        $gd = $days + 1;
        $sal_a = [0, 31, (($gy % 4 === 0 && $gy % 100 !== 0) || ($gy % 400 === 0)) ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        $gm = 0;
        for ($gm = 1; $gm <= 12 && $gd > $sal_a[$gm]; $gm++) {
            $gd -= $sal_a[$gm];
        }

        return [$gy, $gm, $gd];
    }

    /**
     * قالب‌بندی تاریخ/ساعت جلالی از یک رشته تاریخ
     * پشتیبانی از: Y سال کامل، y سال کوتاه، m/n ماه عددی، d/j روز، F نام ماه،
     * l نام روز هفته، H ساعت، i دقیقه، s ثانیه، G ساعت بدون صفر
     */
    public static function format(?string $datetime, string $pattern = 'Y/m/d', bool $persianDigits = true): string
    {
        if (empty($datetime)) {
            return '—';
        }
        $ts = strtotime($datetime);
        if ($ts === false) {
            return '—';
        }

        [$jy, $jm, $jd] = self::toJalali((int) date('Y', $ts), (int) date('n', $ts), (int) date('j', $ts));

        $weekdayNum = (int) date('N', $ts); // 1=دوشنبه ... 7=یکشنبه؛ N: 1=Monday..7=Sunday
        // در گاه‌شمار ایران: شنبه اول هفته. N=6 (Saturday) → شنبه(0)، N=7 (Sunday) → یک‌شنبه(1)، ...
        $jWeekday = ((int) date('w', $ts) + 1) % 7; // w: 0=Sunday..6=Saturday → 1=شنبه ... 6=جمعه، 0=جمعه؟
        // ساده‌سازی صحیح: w=6 (Saturday) => شنبه = 0
        $jWeekday = ((int) date('w', $ts) + 1) % 7;

        $replacements = [
            'Y' => sprintf('%04d', $jy),
            'y' => sprintf('%02d', $jy % 100),
            'm' => sprintf('%02d', $jm),
            'n' => (string) $jm,
            'd' => sprintf('%02d', $jd),
            'j' => (string) $jd,
            'F' => self::MONTHS[$jm - 1],
            'l' => self::WEEKDAYS[$jWeekday],
            'H' => date('H', $ts),
            'i' => date('i', $ts),
            's' => date('s', $ts),
            'G' => (string) (int) date('G', $ts),
        ];
        $replacements['w'] = (string) $jWeekday;

        $out = '';
        $chars = preg_split('//u', $pattern, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        foreach ($chars as $ch) {
            $out .= $replacements[$ch] ?? $ch;
        }

        if ($persianDigits) {
            $out = self::digits($out);
        }

        return $out;
    }

    /**
     * تبدیل ارقام لاتین به فارسی
     */
    public static function digits(string $text): string
    {
        return str_replace(
            ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
            ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'],
            $text
        );
    }

    /**
     * تبدیل ارقام فارسی/عربی به لاتین (برای ورودی فرم‌ها)
     */
    public static function toLatinDigits(string $text): string
    {
        return str_replace(
            ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹', '٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'],
            ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
            $text
        );
    }
}
