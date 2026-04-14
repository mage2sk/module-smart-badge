<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Controller\Adminhtml\Product;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Get products by comma-separated IDs
 * Used for loading existing selected products when editing a badge rule
 */
class GetByIds extends Action
{
    const ADMIN_RESOURCE = 'Panth_SmartBadge::rule_save';

    protected $jsonFactory;
    protected $productCollectionFactory;
    protected $storeManager;
    protected $logger;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        CollectionFactory $productCollectionFactory,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->productCollectionFactory = $productCollectionFactory;
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
                    'products' => []
                ]);
            }

            // Convert comma-separated string to array of integers
            $idArray = array_map('intval', array_filter(explode(',', $ids)));

            if (empty($idArray)) {
                return $result->setData([
                    'success' => true,
                    'products' => []
                ]);
            }

            // Create product collection
            $collection = $this->productCollectionFactory->create();

            // Add basic attributes
            $collection->addAttributeToSelect(['name', 'sku', 'price', 'type_id', 'status']);

            // Filter by IDs
            $collection->addFieldToFilter('entity_id', ['in' => $idArray]);

            // Add store filter
            $collection->addStoreFilter($this->storeManager->getStore()->getId());

            // Prepare products array
            $products = [];
            foreach ($collection as $product) {
                $products[] = [
                    'id' => (int)$product->getId(),
                    'name' => $product->getName(),
                    'sku' => $product->getSku(),
                    'price' => number_format((float)$product->getPrice(), 2, '.', '')
                ];
            }

            return $result->setData([
                'success' => true,
                'products' => $products
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Product GetByIds error: ' . $e->getMessage());
            return $result->setData([
                'success' => false,
                'error' => true,
                'message' => __('An error occurred while fetching products: %1', $e->getMessage())
            ]);
        }
    }
}
