<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Controller\Adminhtml\Category;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class Tree extends Action
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
            $collection = $this->categoryCollectionFactory->create();

            $collection->addAttributeToSelect(['name', 'level', 'path', 'is_active', 'position'])
                ->addAttributeToFilter('is_active', 1)
                ->addAttributeToFilter('entity_id', ['neq' => 1])
                ->setOrder('level', 'ASC')
                ->setOrder('position', 'ASC');

            $collection->load();

            $categoryMap = [];
            foreach ($collection as $category) {
                $categoryMap[(int)$category->getId()] = [
                    'id' => (int)$category->getId(),
                    'name' => $category->getName(),
                    'level' => (int)$category->getLevel(),
                    'path' => $category->getPath(),
                    'position' => (int)$category->getPosition(),
                    'children' => []
                ];
            }

            $tree = $this->buildTree($categoryMap);

            return $result->setData([
                'success' => true,
                'categories' => $tree
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Category tree fetch error: ' . $e->getMessage());
            return $result->setData([
                'success' => false,
                'error' => true,
                'message' => __('An error occurred while fetching categories: %1', $e->getMessage())
            ]);
        }
    }

    protected function buildTree(array $categories): array
    {
        $tree = [];

        $categoryRefs = [];
        foreach ($categories as $id => $category) {
            $categoryRefs[$id] = &$categories[$id];
        }

        foreach ($categories as $id => $category) {
            $pathIds = explode('/', $category['path']);

            array_pop($pathIds);

            if (!empty($pathIds)) {
                $parentId = (int)end($pathIds);

                if ($parentId === 1) {
                    $tree[] = &$categoryRefs[$id];
                } elseif (isset($categoryRefs[$parentId])) {
                    $categoryRefs[$parentId]['children'][] = &$categoryRefs[$id];
                }
            }
        }

        $tree = $this->cleanTree($tree);

        return $tree;
    }

    protected function cleanTree(array $tree): array
    {
        $cleaned = [];

        foreach ($tree as $node) {
            $cleanedNode = [
                'id' => $node['id'],
                'name' => $node['name'],
                'level' => $node['level'],
                'path' => $node['path'],
                'children' => []
            ];

            if (!empty($node['children'])) {
                $cleanedNode['children'] = $this->cleanTree($node['children']);
            }

            $cleaned[] = $cleanedNode;
        }

        return $cleaned;
    }
}
