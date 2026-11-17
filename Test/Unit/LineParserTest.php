<?php
declare(strict_types=1);

namespace MageOS\QuickOrder\Test\Unit;

use MageOS\QuickOrder\Model\LineParser;
use PHPUnit\Framework\TestCase;

/**
 * Tests the quick order line parser.
 */
class LineParserTest extends TestCase
{
    /**
     * Separators, BOM, header row, quotes, blank lines, duplicates and invalid quantities.
     */
    public function testParse(): void
    {
        $text = "\u{FEFF}sku,qty\r\n24-MB01,2\n24-MB02;3\n\n\"24-MB03\"\t1.5\n24-MB04\n24-MB01, 1\n24-MB05,abc\n";

        $this->assertSame(
            ['24-MB01' => 3.0, '24-MB02' => 3.0, '24-MB03' => 1.5, '24-MB04' => 1.0, '24-MB05' => 1.0],
            array_map('floatval', (new LineParser())->parse($text))
        );
    }
}
