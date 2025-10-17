<?php
declare(strict_types=1);

use carry0987\Utils\Utils;
use PHPUnit\Framework\TestCase;

final class UtilsTest extends TestCase
{
    public function testCheckEmpty_AllKeysPresentAndNotEmpty(): void
    {
        $arr = ['a' => 1, 'b' => 'x', 'c' => [0]];
        $this->assertTrue(Utils::checkEmpty($arr));
    }

    public function testCheckEmpty_SpecificKeysAllowEmptyFalse(): void
    {
        $arr = ['a' => 0, 'b' => '', 'c' => 'ok'];
        $this->assertFalse(Utils::checkEmpty($arr, ['a', 'c'], false));
        $this->assertTrue(Utils::checkEmpty($arr, ['c'], false));
    }

    public function testCheckEmpty_AllowEmptyTrue(): void
    {
        $arr = ['a' => 0, 'b' => '', 'c' => null];
        $this->assertTrue(Utils::checkEmpty($arr, ['a', 'b'], true));
        $this->assertFalse(Utils::checkEmpty($arr, ['c'], true));
    }

    public function testOrderArray_BasicOrdering(): void
    {
        $input  = ['b' => 2, 'a' => 1, 'c' => 3];
        $order  = ['a', 'b'];
        $actual = Utils::orderArray($input, $order, false); // $keetNotExists=false
        $this->assertSame(['a' => 1, 'b' => 2], $actual);
    }

    public function testTrimPath_NormalizesSeparators(): void
    {
        $path = "aa\\bb//cc\\dd";
        $normalized = Utils::trimPath($path);
        $this->assertIsString($normalized);
        $this->assertNotEmpty($normalized);
        $this->assertStringNotContainsString('\\', $normalized);
        $this->assertStringContainsString(DIRECTORY_SEPARATOR, $normalized);
    }

    public function testGetISODateTime_FromTimestampAndString(): void
    {
        $ts  = 1730000000;
        $iso = Utils::getISODateTime($ts, 'UTC');
        $this->assertNotNull($iso);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+00:00$/', $iso);

        $iso2 = Utils::getISODateTime('2025-10-17 12:00:00', 'UTC');
        $this->assertNotNull($iso2);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T12:00:00\+00:00$/', $iso2);
    }

    public function testTimestampToDate_And_GetPathByDate(): void
    {
        $ts = 1730000000;
        $date = Utils::timestampToDate($ts, 'Y/m/d/');
        $this->assertSame('2024/10/27/', $date);

        $path = Utils::getPathByDate($ts);
        $this->assertEquals(str_replace('/', DIRECTORY_SEPARATOR, '2024/10/27/'), $path);
    }

    public function testHasAnyQueryParam(): void
    {
        $expected = ['page', 'q'];
        $query = ['q' => 'abc', 'foo' => 'bar'];
        $this->assertTrue(Utils::hasAnyQueryParam($expected, $query));
        $this->assertFalse(Utils::hasAnyQueryParam(['x', 'y'], $query));
    }

    public function testMakePath_And_MakeFilePath(): void
    {
        $base = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'utils_test_' . uniqid();
        $nested = $base . DIRECTORY_SEPARATOR . 'a' . DIRECTORY_SEPARATOR . 'b';
        $this->assertTrue(Utils::makePath($nested));

        $file = $nested . DIRECTORY_SEPARATOR . 'file.txt';
        $this->assertTrue(Utils::makeFilePath($file));

        @unlink($file);
        @rmdir($nested);
        @rmdir($base . DIRECTORY_SEPARATOR . 'a');
        @rmdir($base);
    }

    public function testGenerateRandom_LengthAndCharset(): void
    {
        $s1 = Utils::generateRandom(12, 0);
        $this->assertSame(12, strlen($s1));

        $s2 = Utils::generateRandom(10, 1);
        $this->assertSame(10, strlen($s2));
        $this->assertMatchesRegularExpression('/^[0-9]+$/', $s2);
    }

    public function testFormatFileSize(): void
    {
        $this->assertSame('512 Byte', Utils::formatFileSize(512));
        $this->assertSame('1.00 KB', Utils::formatFileSize(1024));
        $this->assertSame('1.00 MB', Utils::formatFileSize(1024*1024));
    }

    public function testInputFilter_And_ArraySanitize(): void
    {
        $filtered = Utils::inputFilter("  <script>alert('x')</script>  ");
        $this->assertSame('&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt;', $filtered);

        $arr = ['a' => " 'x' ", 'b' => "<b>y</b>", 'c' => 'keep'];
        $sanAll = Utils::arraySanitize($arr);
        $this->assertSame('&quot;x&quot;', $sanAll['a']);
        $this->assertSame('&lt;b&gt;y&lt;/b&gt;', $sanAll['b']);
        $this->assertSame('keep', $sanAll['c']);

        $sanSel = Utils::arraySanitize($arr, ['b']);
        $this->assertSame('<b>y</b>', $sanSel['b']);
        $this->assertSame(" 'x' ", $sanSel['a']);
    }

    public function testValidateInteger(): void
    {
        $this->assertTrue(Utils::validateInteger(10));
        $this->assertTrue(Utils::validateInteger('123'));
        $this->assertFalse(Utils::validateInteger(null));
        $this->assertFalse(Utils::validateInteger('12.3'));
        $this->assertFalse(Utils::validateInteger('-1'));
    }

    public function testConcateURL(): void
    {
        $u1 = Utils::concateURL('https://example.com', ['a' => '1', 'b' => '中']);
        $this->assertSame('https://example.com?a=1&b=%E4%B8%AD', $u1);

        $u2 = Utils::concateURL('https://example.com?x=1', ['a' => '1']);
        $this->assertSame('https://example.com?x=1&a=1', $u2);
    }

    public function testSortData_ASC_DESC(): void
    {
        $data = [
            ['id' => 3, 'name' => 'c'],
            ['id' => 1, 'name' => 'a'],
            ['id' => 2, 'name' => 'b'],
        ];

        $asc = Utils::sortData($data, Utils::ORDER_ASC, 'id');
        $this->assertSame([['id'=>1,'name'=>'a'],['id'=>2,'name'=>'b'],['id'=>3,'name'=>'c']], array_values($asc));

        $desc = Utils::sortData($data, Utils::ORDER_DESC, 'id');
        $this->assertSame([['id'=>3,'name'=>'c'],['id'=>2,'name'=>'b'],['id'=>1,'name'=>'a']], array_values($desc));
    }

    public function testToIntegerAndToFloat(): void
    {
        $i = Utils::toInteger(12.3456, 2);
        $this->assertSame(1234, $i);

        $f = Utils::toFloat(1234, 2);
        $this->assertSame(12.34, $f);
    }

    public function testXxHash_String_IfAvailable(): void
    {
        if (!in_array('xxh64', hash_algos(), true)) {
            $this->markTestSkipped('xxh64 algorithm not available on this runner.');
        }
        $hash = Utils::xxHash('hello', 0, 'xxh64');
        $this->assertMatchesRegularExpression('/^[0-9a-f]{16}$/i', $hash);
    }

    public function testXxHash_File_IfAvailable(): void
    {
        if (!in_array('xxh64', hash_algos(), true)) {
            $this->markTestSkipped('xxh64 algorithm not available on this runner.');
        }

        $tmp = tempnam(sys_get_temp_dir(), 'xxh_');
        file_put_contents($tmp, 'hello world');
        $hash = Utils::xxHashFile($tmp, 0, 'xxh64');
        $this->assertIsString($hash);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{16}$/i', $hash);
        @unlink($tmp);
    }
}
