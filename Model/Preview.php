<?php
declare(strict_types=1);

namespace MageOS\QuickOrder\Model;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Image;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Describes the lines of a quick order before they go to the cart.
 *
 * Errors are the ones the cart would raise: each line is added to a quote that is never saved.
 */
class Preview
{
    /** Rows of one preview: 2 form fields each, which stays within PHP's default max_input_vars (1000). */
    private const MAX_ROWS = 500;

    /**
     * @param ProductRepositoryInterface $products
     * @param StoreManagerInterface $storeManager
     * @param QuoteFactory $quoteFactory
     * @param Image $imageHelper
     * @param PriceCurrencyInterface $priceCurrency
     */
    public function __construct(
        private readonly ProductRepositoryInterface $products,
        private readonly StoreManagerInterface $storeManager,
        private readonly QuoteFactory $quoteFactory,
        private readonly Image $imageHelper,
        private readonly PriceCurrencyInterface $priceCurrency
    ) {
    }

    /**
     * One row per SKU, matrix rows last.
     *
     * A configurable SKU becomes a row with a "matrix" of its variants, which takes over the lines of its children.
     *
     * @param array<int|string,float> $lines qty by SKU
     * @return array<int, array<string, mixed>> rows: sku, qty, name, image, price, salable, error, matrix
     * @throws LocalizedException when the lines and the variants of their configurable products exceed MAX_ROWS
     */
    public function build(array $lines): array
    {
        // ponytail: one full product load per line and per variant; switch to collections if previews get slow.
        $quote = $this->quoteFactory->create();
        $budget = self::MAX_ROWS - count($lines);
        $matrices = [];
        foreach (array_keys($lines) as $sku) {
            $product = $this->find((string) $sku);
            if ($product && $product->getTypeId() === Configurable::TYPE_CODE) {
                $matrices[] = ['matrix' => $this->matrix($product, $lines, $quote, $budget)]
                    + $this->describe((string) $sku, 0, $quote);
                unset($lines[$sku]);
            }
        }
        $rows = [];
        foreach ($lines as $sku => $qty) {
            $rows[] = $this->describe((string) $sku, $qty, $quote);
        }
        return [...$rows, ...$matrices];
    }

    /**
     * Variants grid of a configurable product: the last attribute makes the columns, the others the lines.
     *
     * @param Product $parent
     * @param array<int|string,float> $lines qty by SKU; the variants found in it are removed
     * @param Quote $quote
     * @param int $budget rows left before MAX_ROWS; the variants are taken from it
     * @return array<string, mixed> corner (attribute labels), columns (labels), lines (label, cells: rows)
     * @throws LocalizedException
     */
    private function matrix(Product $parent, array &$lines, Quote $quote, int &$budget): array
    {
        /** @var Configurable $type */
        $type = $parent->getTypeInstance();
        $children = $type->getUsedProducts($parent);
        $budget -= count($children);
        if ($budget < 0) {
            throw new LocalizedException(__('Please preview fewer configurable products at a time.'));
        }
        $positions = $labels = [];
        foreach ($type->getConfigurableAttributes($parent) as $attribute) {
            $eavAttribute = $attribute->getProductAttribute();
            $options = array_column($eavAttribute->getSource()->getAllOptions(), 'value');
            $positions[$eavAttribute->getAttributeCode()] = array_flip($options);
            $labels[] = (string) $attribute->getLabel();
        }
        $columns = $grid = [];
        foreach ($children as $child) {
            $sku = (string) $child->getSku();
            $variant = $this->find($sku);
            if (!$variant) {
                continue;
            }
            // zero-padded option positions: sorting them puts the options in their admin order
            $key = $label = [];
            foreach ($positions as $code => $order) {
                $key[] = sprintf('%05d', $order[$variant->getData($code)] ?? 0);
                $text = $variant->getAttributeText($code);
                $label[] = is_string($text) ? $text : '';
            }
            $column = (string) array_pop($key);
            $columns[$column] = (string) array_pop($label);
            $line = implode('-', $key);
            $grid[$line]['label'] = implode(' / ', $label);
            $grid[$line]['cells'][$column] = $this->describe($sku, $lines[$sku] ?? 0, $quote);
            unset($lines[$sku]);
        }
        ksort($columns);
        ksort($grid);
        return ['corner' => implode(' / ', $labels), 'columns' => $columns, 'lines' => array_values($grid)];
    }

    /**
     * Row for one SKU; with a quantity, "error" says why the cart would refuse it.
     *
     * @param string $sku
     * @param float $qty
     * @param Quote $quote
     * @return array<string, mixed>
     */
    private function describe(string $sku, float $qty, Quote $quote): array
    {
        $row = ['sku' => $sku, 'qty' => $qty, 'name' => '', 'image' => '', 'price' => '', 'salable' => null,
            'error' => '', 'matrix' => null];
        $product = $this->find($sku);
        if (!$product) {
            return ['error' => (string) __('Unknown SKU.')] + $row;
        }
        if ($qty > 0) {
            try {
                $result = $quote->addProduct($product, $qty);
                $row['error'] = is_string($result) ? $result : '';
            } catch (LocalizedException $e) {
                $row['error'] = $e->getMessage();
            }
        }
        return [
            'name' => (string) $product->getName(),
            'image' => $this->imageHelper->init($product, 'product_thumbnail_image')->getUrl(),
            'price' => $this->priceCurrency->format(
                $product->getPriceInfo()->getPrice('final_price')->getAmount()->getValue(),
                false
            ),
            'salable' => (bool) $product->isSalable(),
        ] + $row;
    }

    /**
     * Enabled product of the current website, or null.
     *
     * @param string $sku
     */
    private function find(string $sku): ?Product
    {
        try {
            $product = $this->products->get($sku);
        } catch (NoSuchEntityException $e) {
            return null;
        }
        return $product instanceof Product
            && (int) $product->getStatus() === Status::STATUS_ENABLED
            && in_array($this->storeManager->getStore()->getWebsiteId(), $product->getWebsiteIds())
            ? $product
            : null;
    }
}
