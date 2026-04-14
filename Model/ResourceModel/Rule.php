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

    /**
     * Set created_at and updated_at timestamps before saving
     *
     * @param AbstractModel $object
     * @return $this
     */
    protected function _beforeSave(AbstractModel $object)
    {
        // Set created_at timestamp if not already set
        if (!$object->getCreatedAt()) {
            $object->setCreatedAt((new \DateTime())->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT));
        }

        // Always update the updated_at timestamp
        $object->setUpdatedAt((new \DateTime())->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT));

        return parent::_beforeSave($object);
    }
}
