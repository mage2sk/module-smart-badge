<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Model\Config\Source;

use Magento\Customer\Model\ResourceModel\Group\CollectionFactory as GroupCollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;

class CustomerGroups implements OptionSourceInterface
{
    private ?array $options = null;

    public function __construct(
        private readonly GroupCollectionFactory $groupCollectionFactory
    ) {
    }

    public function toOptionArray(): array
    {
        if ($this->options === null) {
            $this->options = [];
            foreach ($this->groupCollectionFactory->create()->setOrder('customer_group_id', 'ASC') as $group) {
                $this->options[] = [
                    'value' => (string)$group->getId(),
                    'label' => (string)$group->getCode(),
                ];
            }
        }

        return $this->options;
    }
}
