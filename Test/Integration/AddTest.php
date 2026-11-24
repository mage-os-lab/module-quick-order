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
     * A known SKU lands in the cart with its quantity; a refused one comes back in the table with its error.
     */
    #[DataFixture(ProductFixture::class, ['sku' => 'quick-order-simple'])]
    public function testAddsKnownSkusAndShowsRefusedOnesAgain(): void
    {
        $this->post("sku,qty\nquick-order-simple,2\nquick-order-missing,1\nquick-order-simple-2,abc");

        $body = (string) $this->getResponse()->getBody();
        $this->assertMatchesRegularExpression('/value="quick-order-missing".*Unknown SKU\./s', $body);
        $this->assertMatchesRegularExpression(
            '/value="quick-order-simple-2".*Please enter a quantity greater than 0\./s',
            $body
        );
        $this->assertStringNotContainsString('value="quick-order-simple"', $body);
        $this->assertSame(['quick-order-simple' => 2.0], $this->cartItems());
        $this->assertSessionMessages(
            $this->callback(fn (array $messages) => count($messages) === 1
                && str_starts_with($messages[0], '2 line(s) could not be added.')),
            MessageInterface::TYPE_ERROR
        );
    }

    /**
     * When every line is added, the customer lands on the cart.
     */
    #[DataFixture(ProductFixture::class, ['sku' => 'quick-order-simple'])]
    public function testGoesToTheCartWhenEveryLineIsAdded(): void
    {
        $this->post('quick-order-simple;3');

        $this->assertRedirect($this->stringContains('checkout/cart'));
        $this->assertSame(['quick-order-simple' => 3.0], $this->cartItems());
    }

    /**
     * Post lines to the "add" action.
     *
     * @param string $lines
     */
    private function post(string $lines): void
    {
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST)->setPostValue([
            'form_key' => $this->_objectManager->get(FormKey::class)->getFormKey(),
            'lines' => $lines,
        ]);
        $this->dispatch('quickorder/index/add');
    }

    /**
     * Quantities by SKU of the cart.
     *
     * @return array<string, float>
     */
    private function cartItems(): array
    {
        $items = $this->_objectManager->get(Cart::class)->getQuote()->getAllVisibleItems();
        return array_combine(
            array_map(fn ($item) => $item->getSku(), $items),
            array_map(fn ($item) => (float) $item->getQty(), $items)
        );
    }
}
