<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Controller\Badge;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Panth\SmartBadge\Helper\BadgeHelper;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;

class Get implements HttpPostActionInterface, CsrfAwareActionInterface
{
    private $jsonFactory;
    private $request;
    private $badgeHelper;
    private $productRepository;

    public function __construct(
        JsonFactory $jsonFactory,
        RequestInterface $request,
        BadgeHelper $badgeHelper,
        ProductRepositoryInterface $productRepository
    ) {
        $this->jsonFactory = $jsonFactory;
        $this->request = $request;
        $this->badgeHelper = $badgeHelper;
        $this->productRepository = $productRepository;
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

        $result->setHttpResponseCode(200);
        $result->setHeader('Content-Type', 'application/json; charset=utf-8', true);

        try {
            $content = $this->request->getContent();
            $data = json_decode($content, true);
            $productId = is_array($data) && isset($data['product_id'])
                ? $data['product_id']
                : $this->request->getParam('product_id');
            $productId = is_scalar($productId) ? (int)$productId : 0;

            if ($productId <= 0) {
                return $result->setData(['badges' => []]);
            }

            $product = $this->productRepository->getById($productId);

            if (!$product || !$product->getId()
                || (int)$product->getStatus() !== ProductStatus::STATUS_ENABLED
            ) {
                return $result->setData(['badges' => []]);
            }

            $badges = $this->badgeHelper->getProductBadges($product);

            return $result->setData(['badges' => $badges]);
        } catch (\Exception $e) {
            return $result->setData(['badges' => []]);
        }
    }
}
