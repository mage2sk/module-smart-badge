<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class BadgeType implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => 'new', 'label' => __('New')],
            ['value' => 'sale', 'label' => __('Sale')],
            ['value' => 'hot', 'label' => __('Hot')],
            ['value' => 'limited', 'label' => __('Limited')],
            ['value' => 'bestseller', 'label' => __('Bestseller')],
            ['value' => 'trending', 'label' => __('Trending')],
            ['value' => 'exclusive', 'label' => __('Exclusive')],
            ['value' => 'featured', 'label' => __('Featured')],
        ];
    }
}
