<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class DisplayOn implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => 'all', 'label' => __('All Pages')],
            ['value' => 'product', 'label' => __('Product Detail Page Only')],
            ['value' => 'category', 'label' => __('Category Page Only')],
            ['value' => 'slider', 'label' => __('Sliders Only')],
        ];
    }
}
