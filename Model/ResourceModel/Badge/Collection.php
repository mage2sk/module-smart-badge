<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Model\ResourceModel\Badge;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Panth\SmartBadge\Model\Badge as BadgeModel;
use Panth\SmartBadge\Model\ResourceModel\Badge as BadgeResource;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'badge_id';

    protected function _construct()
    {
        $this->_init(BadgeModel::class, BadgeResource::class);
    }
}
