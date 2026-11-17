<?php
declare(strict_types=1);

namespace MageOS\QuickOrder\Controller\Index;

use Exception;
use MageOS\QuickOrder\Model\LineParser;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Cart;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\Http as Request;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\File\UploaderFactory;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Message\ManagerInterface;

/**
 * Adds the posted SKU lines and/or uploaded CSV file to the cart.
 *
 * shortcut: products with required options (configurable, bundle...) are rejected with core's message;
 * order the child SKU instead.
 */
class Add implements HttpPostActionInterface
{
    private const MAX_LINES = 200;
    private const MAX_FILE_SIZE = 1048576;

    /**
     * @param Request $request
     * @param LineParser $parser
     * @param ProductRepositoryInterface $products
     * @param Cart $cart
     * @param ManagerInterface $messages
     * @param RedirectFactory $redirectFactory
     * @param UploaderFactory $uploaderFactory
     * @param File $fileDriver
     */
    public function __construct(
        private readonly Request $request,
        private readonly LineParser $parser,
        private readonly ProductRepositoryInterface $products,
        private readonly Cart $cart,
        private readonly ManagerInterface $messages,
        private readonly RedirectFactory $redirectFactory,
        private readonly UploaderFactory $uploaderFactory,
        private readonly File $fileDriver
    ) {
    }

    /**
     * Add every valid SKU, report the others, then go to the cart (or back to the form when nothing was added).
     */
    public function execute()
    {
        $redirect = $this->redirectFactory->create();
        $text = (string) $this->request->getParam('lines');
        $file = $this->request->getFiles('csv');
        if (!empty($file['tmp_name'])) {
            try {
                $uploader = $this->uploaderFactory->create(['fileId' => 'csv']);
                $uploader->setAllowedExtensions(['csv', 'txt']);
                $allowed = $uploader->checkAllowedExtension($uploader->getFileExtension());
            } catch (Exception $e) {
                $allowed = false;
            }
            if (!$allowed || $file['size'] > self::MAX_FILE_SIZE) {
                $this->messages->addErrorMessage(
                    $allowed ? __('The CSV file is too large.') : __('Please upload a .csv or .txt file.')
                );
                return $redirect->setPath('quickorder');
            }
            $text .= "\n" . $this->fileDriver->fileGetContents($file['tmp_name']);
        }

        $lines = $this->parser->parse($text);
        if (!$lines || count($lines) > self::MAX_LINES) {
            $this->messages->addErrorMessage(
                $lines
                    ? __('Please order at most %1 SKUs at a time.', self::MAX_LINES)
                    : __('Please enter at least one SKU.')
            );
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
