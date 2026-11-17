<?php
declare(strict_types=1);

namespace MageOS\QuickOrder\Test\Integration;

use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Checkout\Model\Cart;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Message\MessageInterface;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestFramework\TestCase\AbstractController;

/**
 * Tests the quick order form submission against a real cart.
 */
#[DbIsolation(true)]
class AddTest extends AbstractController
{
    /**
     * A known SKU lands in the cart with its quantity; an unknown one is reported without blocking the rest.
     */
    #[DataFixture(ProductFixture::class, ['sku' => 'quick-order-simple'])]
    public function testAddsKnownSkusAndReportsUnknownOnes(): void
    {
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST)->setPostValue([
            'form_key' => $this->_objectManager->get(FormKey::class)->getFormKey(),
            'lines' => "sku,qty\nquick-order-simple,2\nquick-order-missing,1",
        ]);
        $this->dispatch('quickorder/index/add');

        $this->assertRedirect($this->stringContains('checkout/cart'));
        $items = $this->_objectManager->get(Cart::class)->getQuote()->getAllVisibleItems();
        $this->assertSame(['quick-order-simple' => 2.0], array_combine(
            array_map(fn ($item) => $item->getSku(), $items),
            array_map(fn ($item) => (float) $item->getQty(), $items)
        ));
        $this->assertSessionMessages(
            $this->callback(fn (array $messages) => count($messages) === 1
                && str_starts_with($messages[0], 'quick-order-missing: ')),
            MessageInterface::TYPE_ERROR
        );
    }
}
