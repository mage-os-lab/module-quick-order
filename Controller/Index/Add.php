<?php
declare(strict_types=1);

namespace MageOS\QuickOrder\Controller\Index;

use MageOS\QuickOrder\Model\Access;
use MageOS\QuickOrder\Model\FormPage;
use MageOS\QuickOrder\Model\Preview;
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
     * @param Access $access
     * @param Preview $preview
     * @param FormPage $formPage
     */
    public function __construct(
        private readonly RequestLines $lines,
        private readonly ProductRepositoryInterface $products,
        private readonly Cart $cart,
        private readonly ManagerInterface $messages,
        private readonly RedirectFactory $redirectFactory,
        private readonly Access $access,
        private readonly Preview $preview,
        private readonly FormPage $formPage
    ) {
    }

    /**
     * Add every valid line, then go to the cart; refused lines come back in the table, with the cart's error.
     */
    public function execute()
    {
        $this->access->check();
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
        $refused = $errors = [];
        foreach ($lines as $sku => $qty) {
            try {
                if ($qty <= 0) {
                    throw new LocalizedException(__('Please enter a quantity greater than 0.'));
                }
                $this->cart->addProduct((int) $this->products->get((string) $sku)->getId(), ['qty' => $qty]);
                $added++;
            } catch (LocalizedException $e) {
                $refused[$sku] = $qty;
                $errors[$sku] = $e->getMessage();
            }
        }
        if ($added) {
            $this->cart->save();
            $this->messages->addSuccessMessage(__('%1 product(s) added to your cart.', $added));
        }
        if (!$refused) {
            return $redirect->setPath('checkout/cart');
        }
        try {
            $rows = $this->preview->build($refused, $errors);
        } catch (LocalizedException $e) {
            $this->messages->addErrorMessage($e->getMessage());
            return $redirect->setPath('quickorder');
        }
        $this->messages->addErrorMessage(
            __('%1 line(s) could not be added. Fix them below, then add them to the cart again.', count($refused))
        );
        return $this->formPage->create($rows);
    }
}
