<?php
declare(strict_types=1);

namespace MageOS\QuickOrder\Model;

/**
 * Turns pasted lines or CSV content into quantities by SKU.
 */
class LineParser
{
    /**
     * Parse "sku[,qty]" lines (separator: comma, semicolon or tab), skipping blank lines and the header row.
     *
     * A missing qty means 1. On a line split by a semicolon or a tab, a comma is a decimal separator ("SKU;2,5").
     * A qty that is not a number above 0 gives the SKU a quantity of 0, which the caller reports.
     *
     * @param string $text
     * @return array<string, float> qty by SKU, duplicates summed
     */
    public function parse(string $text): array
    {
        $lines = $invalid = [];
        foreach (preg_split('/\R/', $text) ?: [] as $line) {
            $columns = preg_split(preg_match('/[;\t]/', $line) ? '/[;\t]/' : '/,/', $line) ?: [''];
            $sku = trim($columns[0], " \"'\u{FEFF}");
            if ($sku === '' || strcasecmp($sku, 'sku') === 0) {
                continue;
            }
            $qty = str_replace(',', '.', trim($columns[1] ?? '', " \"'"));
            if ($qty !== '' && !(is_numeric($qty) && (float) $qty > 0)) {
                $invalid[$sku] = 0.0;
            }
            $lines[$sku] = ($lines[$sku] ?? 0) + ($qty === '' ? 1 : (float) $qty);
        }
        return array_replace($lines, $invalid);
    }
}
