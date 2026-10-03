<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Badge extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('panth_smart_badge', 'badge_id');
    }
}
