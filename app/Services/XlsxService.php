<?php

namespace App\Services;

use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

/**
 * سرویس کار با XLSX و CSV بدون هیچ پکیج خارجی (فقط ZipArchive + SimpleXML)
 * خواندن شیت اول با sharedStrings و رشته‌های درون‌خطی، و ساخت فایل xlsx حداقلی برای خروجی/نمونه.
 */
class XlsxService
{
    /**
     * خواندن شیت اول فایل xlsx به آرایه سطرها (هر سطر آرایه‌ای از رشته‌ها)
     *
     * @return array<int, array<int, string>>
     */
    public function readXlsx(string $path): array
    {
        if (! is_file($path)) {
            throw new RuntimeException('فایل یافت نشد.');
        }

        $zip = new ZipArchive();
        if (($code = $zip->open($path)) !== true) {
            throw new RuntimeException('باز کردن فایل اکسل ممکن نشد (کد ' . $code . ').');
        }

        try {
            $sharedStrings = $this->parseSharedStrings($zip, $zip->locateName('xl/sharedStrings.xml'));
            $sheetXml = $this->resolveFirstSheet($zip);

            $data = $zip->getFromIndex($sheetXml['index']);
            if ($data === false) {
                throw new RuntimeException('محتوای شیت اکسل خوانده نشد.');
            }

            return $this->parseSheetXml($data, $sharedStrings);
        } finally {
            $zip->close();
        }
    }

    /** ساخت فایل xlsx معتبر حداقلی از آرایه سطرها (inline strings) */
    public function writeXlsx(array $rows, string $path): void
    {
        $sheetRows = '';
        foreach (array_values($rows) as $rIndex => $row) {
            $cells = '';
            foreach (array_values($row) as $cIndex => $value) {
                $ref = $this->cellRef($cIndex, $rIndex + 1);
                if ($value === null || $value === '') {
                    continue;
                }
                if (is_numeric($value) && ! preg_match('/^0\d+/', (string) $value)) {
                    $cells .= '<c r="' . $ref . '"><v>' . $this->escape((string) $value) . '</v></c>';
                } else {
                    $cells .= '<c r="' . $ref . '" t="inlineStr"><is><t xml:space="preserve">'
                        . $this->escape((string) $value) . '</t></is></c>';
                }
            }
            $sheetRows .= '<row r="' . ($rIndex + 1) . '">' . $cells . '</row>';
        }

        $files = [
            '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
                . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
                . '<Default Extension="xml" ContentType="application/xml"/>'
                . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
                . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
                . '</Types>',
            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
                . '</Relationships>',
            'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
                . '<sheets><sheet name="Sheet1" sheetId="1" r:id="rId1"/></sheets>'
                . '</workbook>',
            'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
                . '</Relationships>',
            'xl/worksheets/sheet1.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
                . '<sheetData>' . $sheetRows . '</sheetData>'
                . '</worksheet>',
        ];

        $zip = new ZipArchive();
        $dir = dirname($path);
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('ساخت فایل اکسل ممکن نشد.');
        }
        foreach ($files as $name => $content) {
            $zip->addFromString($name, $content);
        }
        $zip->close();
    }

    /**
     * خواندن CSV با پشتیبانی حذف BOM UTF-8 و تشخیص خودکار جداکننده
     *
     * @return array<int, array<int, string>>
     */
    public function readCsv(string $path): array
    {
        if (! is_file($path)) {
            throw new RuntimeException('فایل یافت نشد.');
        }

        $content = (string) file_get_contents($path);
        // حذف BOMهای رایج
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        $content = str_replace("\0", '', $content);
        if (str_starts_with($content, "\xFF\xFE") || str_starts_with($content, "\xFE\xFF")) {
            $content = mb_convert_encoding(substr($content, 2), 'UTF-8', 'UTF-16LE');
        }

        $lines = preg_split('/\r\n|\r|\n/', trim($content)) ?: [];
        $lines = array_values(array_filter($lines, fn ($l) => trim($l) !== ''));

        // تشخیص جداکننده از سرآغاز
        $first = $lines[0] ?? '';
        $delimiter = substr_count($first, ';') > substr_count($first, ',') ? ';' : ',';

        $rows = [];
        foreach ($lines as $line) {
            $rows[] = array_map(fn ($v) => trim((string) $v), str_getcsv($line, $delimiter));
        }

        return $rows;
    }

    /**
     * خواندن جدول‌های سند Word (docx) — هر جدول به سطرها تبدیل می‌شود؛ در نبود جدول، پاراگراف‌ها
     *
     * @return array<int, array<int, string>>
     */
    public function readDocx(string $path): array
    {
        $zip = new ZipArchive();
        if (($code = $zip->open($path)) !== true) {
            throw new RuntimeException('باز کردن فایل Word ممکن نشد (کد ' . $code . ').');
        }

        try {
            $data = $zip->getFromName('word/document.xml');
            if ($data === false) {
                throw new RuntimeException('سند Word معتبر نیست.');
            }

            $xml = @simplexml_load_string($data);
            if ($xml === false) {
                throw new RuntimeException('ساختار سند Word قابل خواندن نیست.');
            }

            $ns = $xml->getNamespaces(true);
            $w = $ns['w'] ?? 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

            $rows = [];
            $tables = $xml->xpath('//w:tbl');
            if ($tables !== false && $tables !== []) {
                foreach ($tables as $table) {
                    foreach ($table->xpath('.//w:tr') ?: [] as $tr) {
                        $cells = [];
                        foreach ($tr->xpath('./w:tc') ?: [] as $tc) {
                            $text = '';
                            foreach ($tc->xpath('.//w:t') ?: [] as $t) {
                                $text .= (string) $t;
                            }
                            $cells[] = trim($text);
                        }
                        if (array_filter($cells) !== []) {
                            $rows[] = $cells;
                        }
                    }
                }
            }

            if ($rows === []) {
                // در نبود جدول: هر پاراگراف یک سطر تک‌ستونی
                foreach ($xml->xpath('//w:p') ?: [] as $p) {
                    $text = '';
                    foreach ($p->xpath('.//w:t') ?: [] as $t) {
                        $text .= (string) $t;
                    }
                    $text = trim($text);
                    if ($text !== '') {
                        $rows[] = [$text];
                    }
                }
            }

            return $rows;
        } finally {
            $zip->close();
        }
    }

    // ------------------------------------------------------------------
    // توابع خصوصی
    // ------------------------------------------------------------------

    private function parseSharedStrings(ZipArchive $zip, $index): array
    {
        if ($index === false || ($data = $zip->getFromIndex($index)) === false) {
            return [];
        }

        $xml = @simplexml_load_string($data);
        if ($xml === false) {
            return [];
        }

        $strings = [];
        foreach ($xml->children() as $si) {
            $text = '';
            // هم <t> مستقیم و هم رشته‌های ترکیبی <r><t>
            foreach ($si->children() as $child) {
                $localName = $child->getName();
                if ($localName === 't') {
                    $text .= (string) $child;
                } elseif ($localName === 'r') {
                    foreach ($child->children() as $rt) {
                        if ($rt->getName() === 't') {
                            $text .= (string) $rt;
                        }
                    }
                }
            }
            $strings[] = $text;
        }

        return $strings;
    }

    /** یافتن نخستین شیت: sheet1.xml یا نخستین شیت تعریف‌شده در workbook */
    private function resolveFirstSheet(ZipArchive $zip): array
    {
        $index = $zip->locateName('xl/worksheets/sheet1.xml');
        if ($index !== false) {
            return ['index' => $index];
        }

        $workbook = $zip->getFromName('xl/workbook.xml');
        $rels = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($workbook !== false && $rels !== false) {
            $wb = @simplexml_load_string($workbook);
            $relsXml = @simplexml_load_string($rels);
            if ($wb !== false && $relsXml !== false) {
                $rNs = $wb->getNamespaces(true);
                $r = $rNs['r'] ?? 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
                foreach ($wb->sheets->sheet as $sheet) {
                    $rid = (string) $sheet->attributes($r)->id;
                    foreach ($relsXml->children() as $rel) {
                        if ((string) $rel['Id'] === $rid) {
                            $target = ltrim((string) $rel['Target'], '/');
                            $target = str_starts_with($target, 'xl/') ? $target : 'xl/' . $target;
                            $index = $zip->locateName($target);
                            if ($index !== false) {
                                return ['index' => $index];
                            }
                        }
                    }
                }
            }
        }

        // آخرین راه: نخستین فایل داخل worksheets
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string) $zip->getNameIndex($i);
            if (str_contains($name, 'xl/worksheets/') && str_ends_with($name, '.xml')) {
                return ['index' => $i];
            }
        }

        throw new RuntimeException('هیچ شیتی در فایل اکسل یافت نشد.');
    }

    private function parseSheetXml(string $data, array $sharedStrings): array
    {
        $xml = @simplexml_load_string($data);
        if ($xml === false) {
            throw new RuntimeException('ساختار شیت اکسل قابل خواندن نیست.');
        }

        $rows = [];
        foreach ($xml->sheetData->row as $row) {
            $cells = [];
            $lastCol = -1;
            foreach ($row->c as $cell) {
                $ref = (string) $cell['r'];
                $colIndex = $ref !== '' ? $this->colIndexFromRef($ref) : $lastCol + 1;
                $lastCol = $colIndex;

                $type = (string) $cell['t'];
                $value = '';

                if ($type === 'inlineStr') {
                    foreach ($cell->is->children() as $node) {
                        if ($node->getName() === 't') {
                            $value .= (string) $node;
                        }
                    }
                } elseif ($type === 's') {
                    $pos = (int) (string) ($cell->v ?? -1);
                    $value = $sharedStrings[$pos] ?? '';
                } else {
                    $value = (string) ($cell->v ?? '');
                }

                $cells[$colIndex] = trim($value);
            }

            // تکمیل ستون‌های گم‌شده
            if ($cells !== []) {
                $max = max(array_keys($cells));
                for ($i = 0; $i < $max; $i++) {
                    $cells[$i] ??= '';
                }
                ksort($cells);
            }

            $rows[] = array_values($cells);
        }

        return $rows;
    }

    private function colIndexFromRef(string $ref): int
    {
        $letters = preg_replace('/[^A-Z]/', '', strtoupper($ref));
        $index = 0;
        for ($i = 0, $len = strlen((string) $letters); $i < $len; $i++) {
            $index = $index * 26 + (ord($letters[$i]) - 64);
        }

        return max(0, $index - 1);
    }

    private function cellRef(int $col, int $row): string
    {
        $letter = '';
        $col += 1;
        while ($col > 0) {
            $mod = ($col - 1) % 26;
            $letter = chr(65 + $mod) . $letter;
            $col = intdiv($col - $mod, 26);
        }

        return $letter . $row;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
