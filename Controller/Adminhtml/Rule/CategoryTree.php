<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Controller\Adminhtml\Rule;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Store\Model\StoreManagerInterface;

class CategoryTree extends Action
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
        $collection->addAttributeToSelect(['name', 'is_active', 'level', 'parent_id', 'position', 'url_key', 'url_path'])
            ->addAttributeToFilter('is_active', 1)
            ->addAttributeToFilter('level', ['gt' => 0])
            ->setOrder('position', 'ASC');

        $suffix = (string) $this->scopeConfig->getValue('catalog/seo/category_url_suffix', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $baseUrl = $this->storeManager->getStore()->getBaseUrl();

        $categoriesById = [];
        foreach ($collection as $category) {
            $urlPath = $category->getUrlPath() ?: $category->getUrlKey();
            $categoriesById[$category->getId()] = [
                'id' => (int) $category->getId(),
                'name' => $category->getName(),
                'level' => (int) $category->getLevel(),
                'parent_id' => (int) $category->getParentId(),
                'url' => $urlPath ? $baseUrl . $urlPath . $suffix : '',
                'children' => [],
            ];
        }

        $tree = [];
        foreach ($categoriesById as $id => &$cat) {
            if (isset($categoriesById[$cat['parent_id']])) {
                $categoriesById[$cat['parent_id']]['children'][] = &$cat;
            } else {
                $tree[] = &$cat;
            }
        }

        return $this->jsonFactory->create()->setData(['categories' => $tree]);
    }
}
