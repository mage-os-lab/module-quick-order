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
     * Separators, BOM, header row, quotes, blank lines, duplicates and a missing quantity.
     */
    public function testParse(): void
    {
        $text = "\u{FEFF}sku,qty\r\n24-MB01,2\n24-MB02;3\n\n\"24-MB03\"\t1.5\n24-MB04\n24-MB01, 1\n";

        $this->assertSame(
            ['24-MB01' => 3.0, '24-MB02' => 3.0, '24-MB03' => 1.5, '24-MB04' => 1.0],
            array_map('floatval', (new LineParser())->parse($text))
        );
    }

    /**
     * A comma is a decimal separator on a line split by a semicolon or a tab, and a second column otherwise.
     */
    public function testDecimalComma(): void
    {
        $this->assertSame(
            ['A' => 2.5, 'B' => 0.5, 'C' => 2.0],
            array_map('floatval', (new LineParser())->parse("A;2,5\nB\t0,5\nC,2,5"))
        );
    }

    /**
     * A quantity that is not a number above 0 gives the SKU a quantity of 0, even when another line is valid.
     */
    public function testInvalidQuantityIsZero(): void
    {
        $this->assertSame(
            ['A' => 0.0, 'B' => 0.0, 'C' => 0.0, 'D' => 0.0, 'E' => 1.0],
            array_map('floatval', (new LineParser())->parse("A,abc\nB,0\nC,-2\nD,3\nD,x\nE"))
        );
    }
}
