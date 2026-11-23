<?php
declare(strict_types=1);

namespace MageOS\QuickOrder\Test\Integration;

use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Checkout\Model\Cart;
use Magento\ConfigurableProduct\Test\Fixture\Attribute as AttributeFixture;
use Magento\ConfigurableProduct\Test\Fixture\Product as ConfigurableProductFixture;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Data\Form\FormKey;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestFramework\TestCase\AbstractController;

/**
 * Tests the quick order preview table.
 */
#[DbIsolation(true)]
class PreviewTest extends AbstractController
{
    /**
     * Known SKUs are described, the others carry an inline error, and the cart stays empty.
     */
    #[DataFixture(ProductFixture::class, ['sku' => 'quick-order-simple', 'name' => 'Quick Order Simple'])]
    public function testDescribesLinesWithoutTouchingTheCart(): void
    {
        $body = $this->preview(['lines' => "quick-order-simple,2\nquick-order-missing,1"]);

        $this->assertStringContainsString('Quick Order Simple', $body);
        $this->assertMatchesRegularExpression('/value="quick-order-simple".*In stock.*value="2"/s', $body);
        $this->assertMatchesRegularExpression('/value="quick-order-missing".*Unknown SKU\./s', $body);
        $this->assertSame([], $this->_objectManager->get(Cart::class)->getQuote()->getAllVisibleItems());
    }

    /**
     * A configurable SKU shows one quantity input per variant, filled from the lines of its children.
     */
    #[DataFixture(ProductFixture::class, ['sku' => 'quick-order-variant-1'], 'p1')]
    #[DataFixture(ProductFixture::class, ['sku' => 'quick-order-variant-2'], 'p2')]
    #[DataFixture(AttributeFixture::class, as: 'attr')]
    #[DataFixture(
        ConfigurableProductFixture::class,
        ['sku' => 'quick-order-configurable', '_options' => ['$attr$'], '_links' => ['$p1$', '$p2$']]
    )]
    public function testConfigurableSkuBecomesAVariantMatrix(): void
    {
        $body = $this->preview(['lines' => "quick-order-configurable\nquick-order-variant-2,3"]);

        $this->assertMatchesRegularExpression('/name="matrix\[\]"\s+value="quick-order-configurable"/', $body);
        $this->assertMatchesRegularExpression(
            '/name="rows\[(\d+)\]\[sku\]"\s+value="quick-order-variant-1">.*?name="rows\[\1\]\[qty\]"\s+value=""/s',
            $body
        );
        $this->assertMatchesRegularExpression(
            '/name="rows\[(\d+)\]\[sku\]"\s+value="quick-order-variant-2">.*?name="rows\[\1\]\[qty\]"\s+value="3"/s',
            $body
        );
    }

    /**
     * Table rows come back with their quantity, rows set to 0 are dropped, and a malformed "lines" is ignored.
     */
    #[DataFixture(ProductFixture::class, ['sku' => 'quick-order-simple'], 'p1')]
    #[DataFixture(ProductFixture::class, ['sku' => 'quick-order-removed'], 'p2')]
    public function testTableRowsArePreviewedAgain(): void
    {
        $body = $this->preview([
            'lines' => ['not a string'],
            'rows' => [
                1 => ['sku' => 'quick-order-simple', 'qty' => '2'],
                2 => ['sku' => 'quick-order-removed', 'qty' => '0'],
            ],
        ]);

        $this->assertMatchesRegularExpression('/value="quick-order-simple".*value="2"/s', $body);
        $this->assertStringNotContainsString('quick-order-removed', $body);
    }

    /**
     * Post the quick order form to the preview and return the page.
     *
     * @param array<string, mixed> $post
     */
    private function preview(array $post): string
    {
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST)->setPostValue(
            ['form_key' => $this->_objectManager->get(FormKey::class)->getFormKey()] + $post
        );
        $this->dispatch('quickorder');
        return (string) $this->getResponse()->getBody();
    }
}
