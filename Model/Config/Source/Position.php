<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Position implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => 'top-left', 'label' => __('Top Left')],
            ['value' => 'top-right', 'label' => __('Top Right')],
            ['value' => 'bottom-left', 'label' => __('Bottom Left')],
            ['value' => 'bottom-right', 'label' => __('Bottom Right')],
            ['value' => 'top-center', 'label' => __('Top Center')],
            ['value' => 'bottom-center', 'label' => __('Bottom Center')],
            ['value' => 'center-left', 'label' => __('Center Left')],
            ['value' => 'center-right', 'label' => __('Center Right')],
        ];
    }
}
