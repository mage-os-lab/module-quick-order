<?php
declare(strict_types=1);

namespace MageOS\QuickOrder\Controller\Index;

use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Helper;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Request\Http as Request;
use Magento\Framework\Controller\Result\JsonFactory;

/**
 * SKU / name autocomplete of the quick order table: /quickorder/index/suggest?q=...
 */
class Suggest implements HttpGetActionInterface
{
    private const MAX_QUERY_LENGTH = 64;

    /**
     * @param Request $request
     * @param CollectionFactory $collectionFactory
     * @param Visibility $visibility
     * @param JsonFactory $jsonFactory
     * @param Helper $dbHelper
     */
    public function __construct(
        private readonly Request $request,
        private readonly CollectionFactory $collectionFactory,
        private readonly Visibility $visibility,
        private readonly JsonFactory $jsonFactory,
        private readonly Helper $dbHelper
    ) {
    }

    /**
     * Up to 10 products visible on the storefront whose SKU or name contains the query, as [{sku, name}].
     */
    public function execute()
    {
        $query = $this->request->getParam('q');
        $query = is_string($query) ? mb_substr(trim($query), 0, self::MAX_QUERY_LENGTH) : '';
        $products = [];
        if (mb_strlen($query) >= 2) {
            // ponytail: LIKE scan on SKU and name; query the search engine instead if the catalog makes it slow.
            $like = $this->dbHelper->escapeLikeValue($query, ['position' => 'any']);
            $collection = $this->collectionFactory->create()
                ->addAttributeToSelect('name')
                ->addStoreFilter()
                ->setVisibility($this->visibility->getVisibleInSiteIds())
                ->addAttributeToFilter([
                    ['attribute' => 'sku', 'like' => $like],
                    ['attribute' => 'name', 'like' => $like],
                ])
                ->setPageSize(10);
            foreach ($collection as $product) {
                $products[] = ['sku' => $product->getSku(), 'name' => $product->getName()];
            }
        }
        return $this->jsonFactory->create()->setData($products);
    }
}
