<?php

namespace Tests\Unit;

use App\Services\XlsxService;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class XlsxServiceTest extends BaseTestCase
{
    use RefreshDatabase;

    public function test_write_and_read_roundtrip(): void
    {
        $service = new XlsxService();
        $rows = [
            ['متن سؤال', 'گزینه ۱', 'گزینه ۲', 'گزینه ۳', 'گزینه ۴', '2', 'توضیح'],
            ['سؤال دوم', 'الف', 'ب', 'ج', 'د', '1', ''],
        ];

        $path = storage_path('app/test-roundtrip.xlsx');
        @unlink($path);
        $service->writeXlsx($rows, $path);
        $this->assertFileExists($path);
        $this->assertEquals('PK', substr(file_get_contents($path), 0, 2), 'خروجی باید xlsx معتبر باشد');

        $read = $service->readXlsx($path);
        $this->assertCount(2, $read);
        $this->assertEquals('متن سؤال', $read[0][0]);
        $this->assertEquals('گزینه ۴', $read[0][4]);
        $this->assertEquals('سؤال دوم', $read[1][0]);
        @unlink($path);
    }

    public function test_csv_read_with_bom(): void
    {
        $service = new XlsxService();
        $path = storage_path('app/test-bom.csv');
        file_put_contents($path, "\xEF\xBB\xBF" . implode("\n", [
            'متن,گزینه۱,گزینه۲,گزینه۳,گزینه۴,صحیح,توضیح',
            'سؤالcsv,الف,ب,ج,د,3,',
        ]));
        $read = $service->readCsv($path);
        $this->assertCount(2, $read);
        $this->assertEquals('متن', $read[0][0]);
        @unlink($path);
    }
}
