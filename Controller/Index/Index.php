<?php
declare(strict_types=1);

namespace MageOS\QuickOrder\Controller\Index;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\PageFactory;

/**
 * Quick order page: /quickorder.
 */
class Index implements HttpGetActionInterface
{
    /**
     * @param PageFactory $pageFactory
     */
    public function __construct(private readonly PageFactory $pageFactory)
    {
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $page = $this->pageFactory->create();
        $page->getConfig()->getTitle()->set((string) __('Quick Order'));
        return $page;
    }
}
