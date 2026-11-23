<?php
declare(strict_types=1);

namespace MageOS\QuickOrder\Controller\Index;

use MageOS\QuickOrder\Model\RequestLines;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Cart;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;

/**
 * Adds the posted SKU lines, uploaded CSV file and preview table rows to the cart.
 *
 * shortcut: products with required options (configurable, bundle...) are rejected with core's message;
 * order the child SKU instead (the preview's variant matrix does that for configurable products).
 */
class Add implements HttpPostActionInterface
{
    /**
     * @param RequestLines $lines
     * @param ProductRepositoryInterface $products
     * @param Cart $cart
     * @param ManagerInterface $messages
     * @param RedirectFactory $redirectFactory
     */
    public function __construct(
        private readonly RequestLines $lines,
        private readonly ProductRepositoryInterface $products,
        private readonly Cart $cart,
        private readonly ManagerInterface $messages,
        private readonly RedirectFactory $redirectFactory
    ) {
    }

    /**
     * Add every valid SKU, report the others, then go to the cart (or back to the form when nothing was added).
     */
    public function execute()
    {
        $redirect = $this->redirectFactory->create();
        try {
            $lines = $this->lines->get();
            if (!$lines) {
                throw new LocalizedException(__('Please enter at least one SKU.'));
            }
        } catch (LocalizedException $e) {
            $this->messages->addErrorMessage($e->getMessage());
            return $redirect->setPath('quickorder');
        }

        $added = 0;
        foreach ($lines as $sku => $qty) {
            try {
                $this->cart->addProduct((int) $this->products->get((string) $sku)->getId(), ['qty' => $qty]);
                $added++;
            } catch (LocalizedException $e) {
                $this->messages->addErrorMessage(__('%1: %2', $sku, $e->getMessage()));
            }
        }
        if ($added) {
            $this->cart->save();
            $this->messages->addSuccessMessage(__('%1 product(s) added to your cart.', $added));
        }
        return $redirect->setPath($added ? 'checkout/cart' : 'quickorder');
    }
}
