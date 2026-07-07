<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class CombinationMode implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            [
                'value' => 'priority',
                'label' => __('Priority Mode (Single Source)')
            ],
            [
                'value' => 'collect_all',
                'label' => __('Collect All Mode (Multiple Sources)')
            ]
        ];
    }
}
