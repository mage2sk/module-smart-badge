<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Controller\Badge;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Panth\SmartBadge\Helper\BadgeHelper;

class Batch implements HttpPostActionInterface, CsrfAwareActionInterface
{
    public const MAX_PRODUCTS = 60;

    public function __construct(
        private readonly JsonFactory $jsonFactory,
        private readonly RequestInterface $request,
        private readonly BadgeHelper $badgeHelper,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();
        $result->setHeader('Content-Type', 'application/json; charset=utf-8', true);

        $productIds = $this->getRequestedIds();
        $products = [];
        foreach ($productIds as $productId) {
            $products[(string)$productId] = [];
        }

        if (empty($productIds) || !$this->badgeHelper->isEnabled()) {
            return $result->setData(['products' => (object)$products]);
        }

        try {
            $criteria = $this->searchCriteriaBuilder
                ->addFilter('entity_id', $productIds, 'in')
                ->create();
            $items = $this->productRepository->getList($criteria)->getItems();

            foreach ($items as $product) {
                if ((int)$product->getStatus() !== ProductStatus::STATUS_ENABLED) {
                    continue;
                }
                $products[(string)$product->getId()] = $this->badgeHelper->getProductBadges($product);
            }
        } catch (\Exception $e) {
            return $result->setData(['products' => (object)$products]);
        }

        return $result->setData(['products' => (object)$products]);
    }

    private function getRequestedIds(): array
    {
        $data = json_decode((string)$this->request->getContent(), true);
        $raw = is_array($data) && isset($data['product_ids'])
            ? $data['product_ids']
            : $this->request->getParam('product_ids');

        if (is_string($raw)) {
            $raw = explode(',', $raw);
        }
        if (!is_array($raw)) {
            return [];
        }

        $ids = [];
        foreach ($raw as $value) {
            if (!is_scalar($value)) {
                continue;
            }
            $id = (int)$value;
            if ($id > 0) {
                $ids[$id] = $id;
            }
            if (count($ids) >= self::MAX_PRODUCTS) {
                break;
            }
        }

        return array_values($ids);
    }
}
