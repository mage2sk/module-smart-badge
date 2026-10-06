<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class BadgeLayout implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            [
                'value' => 'vertical',
                'label' => __('Vertical Stack (Default)')
            ],
            [
                'value' => 'horizontal',
                'label' => __('Horizontal Row')
            ],
            [
                'value' => 'grid',
                'label' => __('Grid Layout (2 columns)')
            ],
            [
                'value' => 'compact',
                'label' => __('Compact (Overlapping)')
            ]
        ];
    }
}
