<?php
declare(strict_types=1);

namespace MageOS\QuickOrder\Model;

use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

/**
 * Builds the quick order page, with the rows of its table.
 */
class FormPage
{
    /**
     * @param PageFactory $pageFactory
     */
    public function __construct(
        private readonly PageFactory $pageFactory
    ) {
    }

    /**
     * The quick order page showing the given rows.
     *
     * @param array<int,array<string,mixed>> $rows rows of Preview::build()
     */
    public function create(array $rows): Page
    {
        $page = $this->pageFactory->create();
        // the "add" action shows the same page when lines are refused
        $page->addHandle('quickorder_index_index');
        $page->getConfig()->getTitle()->set((string) __('Quick Order'));
        $form = $page->getLayout()->getBlock('quickorder.form');
        if ($form instanceof AbstractBlock) {
            $form->setData('rows', $rows);
        }
        return $page;
    }
}
