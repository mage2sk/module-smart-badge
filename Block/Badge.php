<?php
declare(strict_types=1);
namespace Panth\SmartBadge\Block;

use Magento\Framework\View\Element\Template;
use Panth\SmartBadge\Helper\BadgeHelper;
use Magento\Framework\Registry;
use Magento\Catalog\Api\Data\ProductInterface;

class Badge extends Template
{
    private $badgeHelper;
    private $registry;
    private $product;

    public function __construct(
        Template\Context $context,
        BadgeHelper $badgeHelper,
        Registry $registry,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->badgeHelper = $badgeHelper;
        $this->registry = $registry;
    }

    public function getBadges($product): array
    {
        return $product ? $this->badgeHelper->getProductBadges($product) : [];
    }

    public function isEnabled(): bool
    {
        return $this->badgeHelper->isEnabled();
    }

    public function getCurrentProduct()
    {
        return $this->registry->registry('current_product');
    }

    /**
     * Set product for badge display (useful for product lists)
     *
     * @param ProductInterface $product
     * @return $this
     */
    public function setProduct($product)
    {
        $this->product = $product;
        return $this;
    }

    /**
     * Get product set via setProduct() or fall back to current product
     *
     * @return ProductInterface|null
     */
    public function getProduct()
    {
        return $this->product ?? $this->getCurrentProduct();
    }

    /**
     * Get badge layout setting from config
     *
     * @return string
     */
    public function getBadgeLayout(): string
    {
        return $this->_scopeConfig->getValue('smart_badge/display/badge_layout') ?: 'vertical';
    }

    /**
     * Get badge spacing setting from config
     *
     * @return string
     */
    public function getBadgeSpacing(): string
    {
        return $this->_scopeConfig->getValue('smart_badge/display/badge_spacing') ?: 'gap-2';
    }
}
