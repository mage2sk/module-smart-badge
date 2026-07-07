<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Controller\Adminhtml\Category;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class GetByIds extends Action
{
    const ADMIN_RESOURCE = 'Panth_SmartBadge::rule_save';

    protected $jsonFactory;
    protected $categoryCollectionFactory;
    protected $storeManager;
    protected $logger;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        CollectionFactory $categoryCollectionFactory,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();

        try {
            $ids = $this->getRequest()->getParam('ids', '');

            if (empty($ids)) {
                return $result->setData([
                    'success' => true,
                    'categories' => []
                ]);
            }

            $idArray = array_map('intval', array_filter(explode(',', $ids)));

            if (empty($idArray)) {
                return $result->setData([
                    'success' => true,
                    'categories' => []
                ]);
            }

            $collection = $this->categoryCollectionFactory->create();

            $collection->addAttributeToSelect(['name', 'level', 'path', 'is_active']);

            $collection->addFieldToFilter('entity_id', ['in' => $idArray]);

            $collection->addAttributeToFilter('is_active', 1);

            $categories = [];
            foreach ($collection as $category) {
                $categories[] = [
                    'id' => (int)$category->getId(),
                    'name' => $category->getName(),
                    'level' => (int)$category->getLevel(),
                    'path' => $category->getPath()
                ];
            }

            return $result->setData([
                'success' => true,
                'categories' => $categories
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Category GetByIds error: ' . $e->getMessage());
            return $result->setData([
                'success' => false,
                'error' => true,
                'message' => __('An error occurred while fetching categories: %1', $e->getMessage())
            ]);
        }
    }
}
