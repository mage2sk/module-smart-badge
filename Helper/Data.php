<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    public function __construct(Context $context)
    {
        parent::__construct($context);
    }

    protected function isCoreModuleEnabled()
    {
        return true;
    }

    public function isEnabled($storeId = null)
    {
        if (!$this->isCoreModuleEnabled()) {
            return false;
        }
        return (bool)$this->scopeConfig->getValue(
            'smart_badge/general/enabled',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
