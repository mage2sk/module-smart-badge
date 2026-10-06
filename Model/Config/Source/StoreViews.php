<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Store\Model\System\Store as SystemStore;

class StoreViews implements OptionSourceInterface
{
    public function __construct(
        private readonly SystemStore $systemStore
    ) {
    }

    public function toOptionArray(): array
    {
        return $this->systemStore->getStoreValuesForForm(false, true);
    }
}
