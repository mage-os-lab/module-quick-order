<?php
declare(strict_types=1);

namespace MageOS\QuickOrder\Controller\Index;

use MageOS\QuickOrder\Model\Preview;
use MageOS\QuickOrder\Model\RequestLines;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\Http as Request;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Result\PageFactory;

/**
 * Quick order page: /quickorder. Posting the form to it previews the lines without touching the cart.
 */
class Index implements HttpGetActionInterface, HttpPostActionInterface
{
    /**
     * @param PageFactory $pageFactory
     * @param Request $request
     * @param RequestLines $lines
     * @param Preview $preview
     * @param ManagerInterface $messages
     * @param RedirectFactory $redirectFactory
     */
    public function __construct(
        private readonly PageFactory $pageFactory,
        private readonly Request $request,
        private readonly RequestLines $lines,
        private readonly Preview $preview,
        private readonly ManagerInterface $messages,
        private readonly RedirectFactory $redirectFactory
    ) {
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $rows = [];
        if ($this->request->isPost()) {
            try {
                $rows = $this->preview->build($this->lines->get(true));
            } catch (LocalizedException $e) {
                $this->messages->addErrorMessage($e->getMessage());
                return $this->redirectFactory->create()->setPath('quickorder');
            }
        }
        $page = $this->pageFactory->create();
        $page->getConfig()->getTitle()->set((string) __('Quick Order'));
        $form = $page->getLayout()->getBlock('quickorder.form');
        if ($form instanceof AbstractBlock) {
            $form->setData('rows', $rows);
        }
        return $page;
    }
}
