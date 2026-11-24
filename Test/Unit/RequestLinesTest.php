<?php
declare(strict_types=1);

namespace MageOS\QuickOrder\Test\Unit;

use MageOS\QuickOrder\Model\LineParser;
use MageOS\QuickOrder\Model\RequestLines;
use Magento\Framework\App\Request\Http as Request;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\File\UploaderFactory;
use Magento\Framework\Filesystem\Driver\File;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests how the quick order form is read.
 */
class RequestLinesTest extends TestCase
{
    /**
     * A file PHP refused is reported instead of being ignored; no file at all is fine.
     *
     * @param int $error
     * @param string|null $message
     */
    #[DataProvider('uploadErrors')]
    public function testRefusedUploadIsReported(int $error, ?string $message): void
    {
        $request = $this->createStub(Request::class);
        $request->method('getParam')->willReturnMap([['lines', null, "A,2\nB,abc"], ['rows', null, null]]);
        $request->method('getFiles')->willReturn(['name' => 'lines.csv', 'tmp_name' => '', 'error' => $error]);
        $lines = new RequestLines(
            $request,
            new LineParser(),
            $this->createStub(UploaderFactory::class),
            $this->createStub(File::class)
        );

        if ($message !== null) {
            $this->expectException(LocalizedException::class);
            $this->expectExceptionMessage($message);
        }
        // an invalid quantity stays in the lines, with a quantity of 0
        $this->assertSame(['A' => 2.0, 'B' => 0.0], array_map('floatval', $lines->get()));
    }

    /**
     * @return array<string, array{int, string|null}>
     */
    public static function uploadErrors(): array
    {
        return [
            'no file' => [UPLOAD_ERR_NO_FILE, null],
            'over upload_max_filesize' => [UPLOAD_ERR_INI_SIZE, 'The CSV file is too large.'],
            'partial upload' => [UPLOAD_ERR_PARTIAL, 'The file could not be uploaded. Please try again.'],
        ];
    }
}
