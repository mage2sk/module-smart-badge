<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Model;

use Magento\Framework\Model\AbstractModel;
use Panth\SmartBadge\Model\BadgeFactory;

class Rule extends AbstractModel
{
    private $badgeFactory;
    private $badge;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        BadgeFactory $badgeFactory,
        ?\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        ?\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->badgeFactory = $badgeFactory;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    protected function _construct()
    {
        $this->_init(\Panth\SmartBadge\Model\ResourceModel\Rule::class);
    }

    public function getProductIds(): array
    {
        $ids = $this->getData('product_ids');
        return $ids ? array_filter(array_map('intval', explode(',', $ids))) : [];
    }

    public function getCategoryIds(): array
    {
        $ids = $this->getData('category_ids');
        return $ids ? array_filter(array_map('intval', explode(',', $ids))) : [];
    }

    public function getBadge(): ?Badge
    {
        if ($this->badge === null) {
            $badgeId = (int)$this->getData('badge_id');
            if ($badgeId) {
                $this->badge = $this->badgeFactory->create()->load($badgeId);
                if (!$this->badge->getId()) {
                    $this->badge = null;
                }
            }
        }
        return $this->badge;
    }
}
