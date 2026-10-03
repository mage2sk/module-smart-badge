<?php
declare(strict_types=1);
namespace Panth\SmartBadge\Model\Config\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

class BadgeOptions extends AbstractSource
{
    public function getAllOptions()
    {
        if ($this->_options === null) {
            $this->_options = [
                ['value' => '', 'label' => __('-- Auto Detect --')],
                ['value' => 'new', 'label' => __('🆕 NEW')],
                ['value' => 'sale', 'label' => __('🔥 SALE')],
                ['value' => 'hot', 'label' => __('🔥 HOT')],
                ['value' => 'limited', 'label' => __('⏰ LIMITED')],
                ['value' => 'bestseller', 'label' => __('⭐ BESTSELLER')],
                ['value' => 'trending', 'label' => __('📈 TRENDING')],
                ['value' => 'exclusive', 'label' => __('💎 EXCLUSIVE')],
                ['value' => 'featured', 'label' => __('✨ FEATURED')],
            ];
        }
        return $this->_options;
    }
}
