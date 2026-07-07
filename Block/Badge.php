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

    public function setProduct($product)
    {
        $this->product = $product;
        return $this;
    }

    public function getProduct()
    {
        return $this->product ?? $this->getCurrentProduct();
    }

    public function getBadgeLayout(): string
    {
        return $this->_scopeConfig->getValue('smart_badge/display/badge_layout') ?: 'vertical';
    }

    public function getBadgeSpacing(): string
    {
        return $this->_scopeConfig->getValue('smart_badge/display/badge_spacing') ?: 'gap-2';
    }
}
