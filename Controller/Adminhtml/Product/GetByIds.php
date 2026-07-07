<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Controller\Adminhtml\Product;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

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

            if (empty($ids)) {
                return $result->setData([
                    'success' => true,
                    'products' => []
                ]);
            }

            $idArray = array_map('intval', array_filter(explode(',', $ids)));

            if (empty($idArray)) {
                return $result->setData([
                    'success' => true,
                    'products' => []
                ]);
            }

            $collection = $this->productCollectionFactory->create();

            $collection->addAttributeToSelect(['name', 'sku', 'price', 'type_id', 'status']);

            $collection->addFieldToFilter('entity_id', ['in' => $idArray]);

            $collection->addStoreFilter($this->storeManager->getStore()->getId());

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
