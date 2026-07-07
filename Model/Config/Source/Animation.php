<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Animation implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => 'none', 'label' => __('None')],
            ['value' => 'pulse', 'label' => __('Pulse')],
            ['value' => 'bounce', 'label' => __('Bounce')],
            ['value' => 'shake', 'label' => __('Shake')],
            ['value' => 'glow', 'label' => __('Glow')],
            ['value' => 'rotate', 'label' => __('Rotate')],
            ['value' => 'flip', 'label' => __('Flip')],
            ['value' => 'swing', 'label' => __('Swing')],
            ['value' => 'tada', 'label' => __('Tada')],
            ['value' => 'jello', 'label' => __('Jello')],
            ['value' => 'heartbeat', 'label' => __('Heartbeat')],
            ['value' => 'fadeIn', 'label' => __('Fade In')],
            ['value' => 'fadeOut', 'label' => __('Fade Out')],
            ['value' => 'slideInLeft', 'label' => __('Slide In Left')],
            ['value' => 'slideInRight', 'label' => __('Slide In Right')],
            ['value' => 'slideInUp', 'label' => __('Slide In Up')],
            ['value' => 'slideInDown', 'label' => __('Slide In Down')],
            ['value' => 'zoomIn', 'label' => __('Zoom In')],
            ['value' => 'zoomOut', 'label' => __('Zoom Out')],
            ['value' => 'flash', 'label' => __('Flash')],
            ['value' => 'rubberBand', 'label' => __('Rubber Band')],
            ['value' => 'wobble', 'label' => __('Wobble')],
            ['value' => 'headShake', 'label' => __('Head Shake')],
            ['value' => 'rollIn', 'label' => __('Roll In')],
            ['value' => 'rollOut', 'label' => __('Roll Out')],
        ];
    }
}
