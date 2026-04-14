<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Controller\Adminhtml\Category;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Get categories by comma-separated IDs
 * Used for loading existing selected categories when editing a badge rule
 */
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

            // Handle empty IDs
            if (empty($ids)) {
                return $result->setData([
                    'success' => true,
                    'categories' => []
                ]);
            }

            // Convert comma-separated string to array of integers
            $idArray = array_map('intval', array_filter(explode(',', $ids)));

            if (empty($idArray)) {
                return $result->setData([
                    'success' => true,
                    'categories' => []
                ]);
            }

            // Create category collection
            $collection = $this->categoryCollectionFactory->create();

            // Add necessary attributes
            $collection->addAttributeToSelect(['name', 'level', 'path', 'is_active']);

            // Filter by IDs
            $collection->addFieldToFilter('entity_id', ['in' => $idArray]);

            // Only active categories
            $collection->addAttributeToFilter('is_active', 1);

            // Prepare categories array
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
