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

    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

    /**
     * @var CollectionFactory
     */
    protected $categoryCollectionFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Constructor
     *
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param CollectionFactory $categoryCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     */
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

    /**
     * Execute action
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->jsonFactory->create();

        try {
            // Get all active categories
            $collection = $this->categoryCollectionFactory->create();

            // Add necessary attributes to select
            $collection->addAttributeToSelect(['name', 'level', 'path', 'is_active', 'position'])
                ->addAttributeToFilter('is_active', 1)  // Only active categories
                ->addAttributeToFilter('entity_id', ['neq' => 1])  // Exclude root category (id=1)
                ->setOrder('level', 'ASC')
                ->setOrder('position', 'ASC');

            // Load the collection
            $collection->load();

            // Build category map for tree construction
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

            // Build hierarchical tree structure
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

    /**
     * Build hierarchical tree structure from flat category array
     *
     * @param array $categories
     * @return array
     */
    protected function buildTree(array $categories): array
    {
        $tree = [];

        // Create a reference map for quick access
        $categoryRefs = [];
        foreach ($categories as $id => $category) {
            $categoryRefs[$id] = &$categories[$id];
        }

        // Build the tree by assigning children to their parents
        foreach ($categories as $id => $category) {
            // Get parent ID from path
            $pathIds = explode('/', $category['path']);

            // Remove the category itself from the path
            array_pop($pathIds);

            // Get the immediate parent ID (last item in remaining path)
            if (!empty($pathIds)) {
                $parentId = (int)end($pathIds);

                // Skip root category (id=1) as parent
                if ($parentId === 1) {
                    // This is a top-level category (direct child of root)
                    $tree[] = &$categoryRefs[$id];
                } elseif (isset($categoryRefs[$parentId])) {
                    // Add as child to parent
                    $categoryRefs[$parentId]['children'][] = &$categoryRefs[$id];
                }
            }
        }

        // Remove the 'position' field from the final output (used only for sorting)
        $tree = $this->cleanTree($tree);

        return $tree;
    }

    /**
     * Clean tree by removing internal fields and ensuring proper structure
     *
     * @param array $tree
     * @return array
     */
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

            // Recursively clean children
            if (!empty($node['children'])) {
                $cleanedNode['children'] = $this->cleanTree($node['children']);
            }

            $cleaned[] = $cleanedNode;
        }

        return $cleaned;
    }
}
