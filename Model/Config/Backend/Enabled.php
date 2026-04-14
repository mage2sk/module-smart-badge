<?php
/**
 * Backend Model for SmartBadge Enabled Field
 * License validation removed - standard Value backend
 *
 * @category  Panth
 * @package   Panth_SmartBadge
 */
declare(strict_types=1);

namespace Panth\SmartBadge\Model\Config\Backend;

use Magento\Framework\App\Config\Value;

class Enabled extends Value
{
    /**
     * @return $this
     */
    public function beforeSave()
    {
        return parent::beforeSave();
    }
}
