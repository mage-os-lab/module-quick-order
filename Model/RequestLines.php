<?php
declare(strict_types=1);

namespace MageOS\QuickOrder\Model;

use Exception;
use Magento\Framework\App\Request\Http as Request;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\File\UploaderFactory;
use Magento\Framework\Filesystem\Driver\File;

/**
 * Reads the quick order form: pasted lines, uploaded CSV file and preview table rows.
 */
class RequestLines
{
    private const MAX_LINES = 200;
    private const MAX_FILE_SIZE = 1048576;

    /**
     * @param Request $request
     * @param LineParser $parser
     * @param UploaderFactory $uploaderFactory
     * @param File $fileDriver
     */
    public function __construct(
        private readonly Request $request,
        private readonly LineParser $parser,
        private readonly UploaderFactory $uploaderFactory,
        private readonly File $fileDriver
    ) {
    }

    /**
     * Quantities by SKU from the "lines" text, the "csv" file and the "rows" of the preview table.
     *
     * Table rows with an empty SKU or a quantity of 0 are dropped: that is how a line is removed.
     *
     * @param bool $withMatrices also return the configurable SKUs whose variant matrix is displayed ("matrix")
     * @return array<int|string, float>
     * @throws LocalizedException
     */
    public function get(bool $withMatrices = false): array
    {
        $text = $this->request->getParam('lines');
        $text = is_string($text) ? $text : '';
        $file = $this->request->getFiles('csv');
        if (!empty($file['tmp_name'])) {
            try {
                $uploader = $this->uploaderFactory->create(['fileId' => 'csv']);
                $uploader->setAllowedExtensions(['csv', 'txt']);
                $allowed = $uploader->checkAllowedExtension($uploader->getFileExtension());
            } catch (Exception $e) {
                $allowed = false;
            }
            if (!$allowed) {
                throw new LocalizedException(__('Please upload a .csv or .txt file.'));
            }
            if ($file['size'] > self::MAX_FILE_SIZE) {
                throw new LocalizedException(__('The CSV file is too large.'));
            }
            $text .= "\n" . $this->fileDriver->fileGetContents($file['tmp_name']);
        }

        $lines = $this->parser->parse($text);
        foreach ((array) $this->request->getParam('rows') as $row) {
            $sku = is_array($row) && is_string($row['sku'] ?? null) ? trim($row['sku']) : '';
            $qty = is_array($row) && is_numeric($row['qty'] ?? null) ? (float) $row['qty'] : 0;
            if ($sku !== '' && $qty > 0) {
                $lines[$sku] = ($lines[$sku] ?? 0) + $qty;
            }
        }
        if ($withMatrices) {
            foreach ((array) $this->request->getParam('matrix') as $sku) {
                if (is_string($sku) && trim($sku) !== '') {
                    $lines += [trim($sku) => 1.0];
                }
            }
        }
        if (count($lines) > self::MAX_LINES) {
            throw new LocalizedException(__('Please order at most %1 SKUs at a time.', self::MAX_LINES));
        }
        return $lines;
    }
}
