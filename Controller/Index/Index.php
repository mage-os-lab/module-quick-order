<?php
declare(strict_types=1);

namespace MageOS\QuickOrder\Controller\Index;

use MageOS\QuickOrder\Model\Access;
use MageOS\QuickOrder\Model\FormPage;
use MageOS\QuickOrder\Model\Preview;
use MageOS\QuickOrder\Model\RequestLines;
use Magento\Customer\Model\Url;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\Http as Request;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;

/**
 * Quick order page: /quickorder. Posting the form to it previews the lines without touching the cart.
 */
class Index implements HttpGetActionInterface, HttpPostActionInterface
{
    /**
     * @param FormPage $formPage
     * @param Request $request
     * @param RequestLines $lines
     * @param Preview $preview
     * @param ManagerInterface $messages
     * @param RedirectFactory $redirectFactory
     * @param Access $access
     * @param Url $customerUrl
     */
    public function __construct(
        private readonly FormPage $formPage,
        private readonly Request $request,
        private readonly RequestLines $lines,
        private readonly Preview $preview,
        private readonly ManagerInterface $messages,
        private readonly RedirectFactory $redirectFactory,
        private readonly Access $access,
        private readonly Url $customerUrl
    ) {
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        if ($this->access->needsLogin()) {
            return $this->redirectFactory->create()->setUrl($this->customerUrl->getLoginUrl());
        }
        $this->access->check();

        $rows = [];
        if ($this->request->isPost()) {
            try {
                $rows = $this->preview->build($this->lines->get(true));
            } catch (LocalizedException $e) {
                $this->messages->addErrorMessage($e->getMessage());
                return $this->redirectFactory->create()->setPath('quickorder');
            }
        }
        return $this->formPage->create($rows);
    }
}
