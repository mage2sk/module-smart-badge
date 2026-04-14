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

/**
 * Frontend endpoint: POST /smartbadge/badge/get
 *
 * Read-only JSON endpoint that returns the list of badges for a given product ID.
 * Does not mutate state, accept user input beyond product_id, or expose sensitive
 * information — so it is exempt from CSRF form-key validation.
 */
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

        // Ensure UTF-8 encoding for emojis
        $result->setHttpResponseCode(200);
        $result->setHeader('Content-Type', 'application/json; charset=utf-8', true);

        try {
            // Get product ID from request
            $content = $this->request->getContent();
            $data = json_decode($content, true);
            $productId = $data['product_id'] ?? $this->request->getParam('product_id');

            if (!$productId) {
                return $result->setData(['badges' => []]);
            }

            // Load product
            $product = $this->productRepository->getById($productId);

            if (!$product || !$product->getId()) {
                return $result->setData(['badges' => []]);
            }

            // Get badges for product
            $badges = $this->badgeHelper->getProductBadges($product);

            // Ensure JSON is encoded with proper UTF-8 handling for emojis
            return $result->setData(['badges' => $badges]);

        } catch (\Exception $e) {
            return $result->setData([
                'badges' => [],
                'error' => $e->getMessage()
            ]);
        }
    }
}
