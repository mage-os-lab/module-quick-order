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
     * Missing or invalid qty means 1.
     *
     * @param string $text
     * @return array<string, float> qty by SKU, duplicates summed
     */
    public function parse(string $text): array
    {
        $lines = [];
        foreach (preg_split('/\R/', $text) ?: [] as $line) {
            $columns = preg_split('/[,;\t]/', $line) ?: [''];
            $sku = trim($columns[0], " \"'\u{FEFF}");
            if ($sku === '' || strcasecmp($sku, 'sku') === 0) {
                continue;
            }
            $qty = (float) trim($columns[1] ?? '', " \"'");
            $lines[$sku] = ($lines[$sku] ?? 0) + ($qty > 0 ? $qty : 1);
        }
        return $lines;
    }
}
