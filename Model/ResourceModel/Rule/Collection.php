<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Model\ResourceModel\Rule;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Panth\SmartBadge\Model\Rule as RuleModel;
use Panth\SmartBadge\Model\ResourceModel\Rule as RuleResource;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'rule_id';

    protected function _construct()
    {
        $this->_init(RuleModel::class, RuleResource::class);
    }
}
