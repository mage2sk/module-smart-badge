<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\AbstractModel;

class Rule extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('panth_smart_badge_rule', 'rule_id');
    }

    protected function _beforeSave(AbstractModel $object)
    {
        if (!$object->getCreatedAt()) {
            $object->setCreatedAt((new \DateTime())->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT));
        }

        $object->setUpdatedAt((new \DateTime())->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT));

        return parent::_beforeSave($object);
    }
}
