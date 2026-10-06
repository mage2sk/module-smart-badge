<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Controller\Adminhtml\Rule;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Store\Model\StoreManagerInterface;

class ProductsGrid extends Action
{
    const ADMIN_RESOURCE = 'Panth_SmartBadge::rule_save';

    private CollectionFactory $collectionFactory;
    private JsonFactory $jsonFactory;
    private ScopeConfigInterface $scopeConfig;
    private StoreManagerInterface $storeManager;

    public function __construct(
        Context $context,
        CollectionFactory $collectionFactory,
        JsonFactory $jsonFactory,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->collectionFactory = $collectionFactory;
        $this->jsonFactory = $jsonFactory;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
    }

    public function execute()
    {
        $collection = $this->collectionFactory->create();
        $collection->addAttributeToSelect(['name', 'sku', 'price', 'type_id', 'status', 'url_key', 'visibility'])
            ->addAttributeToFilter('status', \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
            ->addAttributeToFilter('visibility', ['in' => [
                \Magento\Catalog\Model\Product\Visibility::VISIBILITY_IN_CATALOG,
                \Magento\Catalog\Model\Product\Visibility::VISIBILITY_IN_SEARCH,
                \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH,
            ]])
            ->setPageSize(500)
            ->setOrder('entity_id', 'ASC');

        $suffix = (string) $this->scopeConfig->getValue('catalog/seo/product_url_suffix', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $baseUrl = $this->storeManager->getStore()->getBaseUrl();

        $products = [];
        foreach ($collection as $product) {
            $urlKey = $product->getUrlKey() ?: '';
            $products[] = [
                'id' => (int) $product->getId(),
                'name' => (string) $product->getName(),
                'sku' => (string) $product->getSku(),
                'type' => (string) $product->getTypeId(),
                'price' => (float) $product->getPrice(),
                'url' => $urlKey ? $baseUrl . $urlKey . $suffix : '',
            ];
        }

        return $this->jsonFactory->create()->setData(['products' => $products]);
    }
}
