<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class FontAwesomeIcons implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => '', 'label' => __('-- No Icon --')],
            // Shopping & Sales
            ['value' => 'fa-solid fa-tag', 'label' => __('Tag')],
            ['value' => 'fa-solid fa-tags', 'label' => __('Tags')],
            ['value' => 'fa-solid fa-cart-shopping', 'label' => __('Shopping Cart')],
            ['value' => 'fa-solid fa-bag-shopping', 'label' => __('Shopping Bag')],
            ['value' => 'fa-solid fa-basket-shopping', 'label' => __('Basket')],
            ['value' => 'fa-solid fa-percent', 'label' => __('Percent')],
            ['value' => 'fa-solid fa-gift', 'label' => __('Gift')],
            ['value' => 'fa-solid fa-credit-card', 'label' => __('Credit Card')],
            ['value' => 'fa-solid fa-money-bill', 'label' => __('Money')],
            ['value' => 'fa-solid fa-receipt', 'label' => __('Receipt')],
            ['value' => 'fa-solid fa-barcode', 'label' => __('Barcode')],
            // Stars & Badges
            ['value' => 'fa-solid fa-star', 'label' => __('Star')],
            ['value' => 'fa-solid fa-crown', 'label' => __('Crown')],
            ['value' => 'fa-solid fa-trophy', 'label' => __('Trophy')],
            ['value' => 'fa-solid fa-medal', 'label' => __('Medal')],
            ['value' => 'fa-solid fa-award', 'label' => __('Award')],
            ['value' => 'fa-solid fa-gem', 'label' => __('Gem')],
            ['value' => 'fa-solid fa-certificate', 'label' => __('Certificate')],
            ['value' => 'fa-solid fa-ribbon', 'label' => __('Ribbon')],
            // Fire & Energy
            ['value' => 'fa-solid fa-fire', 'label' => __('Fire')],
            ['value' => 'fa-solid fa-fire-flame-curved', 'label' => __('Flame')],
            ['value' => 'fa-solid fa-bolt', 'label' => __('Bolt / Lightning')],
            ['value' => 'fa-solid fa-bolt-lightning', 'label' => __('Lightning')],
            ['value' => 'fa-solid fa-explosion', 'label' => __('Explosion')],
            ['value' => 'fa-solid fa-wand-magic-sparkles', 'label' => __('Magic Sparkles')],
            ['value' => 'fa-solid fa-sparkles', 'label' => __('Sparkles')],
            // Time & Urgency
            ['value' => 'fa-solid fa-clock', 'label' => __('Clock')],
            ['value' => 'fa-solid fa-hourglass-half', 'label' => __('Hourglass')],
            ['value' => 'fa-solid fa-stopwatch', 'label' => __('Stopwatch')],
            ['value' => 'fa-solid fa-bell', 'label' => __('Bell')],
            ['value' => 'fa-solid fa-calendar-check', 'label' => __('Calendar Check')],
            // Status & Alerts
            ['value' => 'fa-solid fa-check', 'label' => __('Check')],
            ['value' => 'fa-solid fa-check-double', 'label' => __('Double Check')],
            ['value' => 'fa-solid fa-circle-check', 'label' => __('Circle Check')],
            ['value' => 'fa-solid fa-circle-exclamation', 'label' => __('Exclamation')],
            ['value' => 'fa-solid fa-circle-info', 'label' => __('Info')],
            ['value' => 'fa-solid fa-triangle-exclamation', 'label' => __('Warning')],
            // Hearts & Love
            ['value' => 'fa-solid fa-heart', 'label' => __('Heart')],
            ['value' => 'fa-solid fa-thumbs-up', 'label' => __('Thumbs Up')],
            ['value' => 'fa-solid fa-hand-holding-heart', 'label' => __('Holding Heart')],
            // Arrows & Trending
            ['value' => 'fa-solid fa-arrow-trend-up', 'label' => __('Trending Up')],
            ['value' => 'fa-solid fa-arrow-trend-down', 'label' => __('Trending Down')],
            ['value' => 'fa-solid fa-chart-line', 'label' => __('Chart Line')],
            ['value' => 'fa-solid fa-rocket', 'label' => __('Rocket')],
            ['value' => 'fa-solid fa-bullseye', 'label' => __('Bullseye')],
            // Shipping & Stock
            ['value' => 'fa-solid fa-truck-fast', 'label' => __('Fast Shipping')],
            ['value' => 'fa-solid fa-truck', 'label' => __('Truck')],
            ['value' => 'fa-solid fa-box', 'label' => __('Box')],
            ['value' => 'fa-solid fa-boxes-stacked', 'label' => __('Boxes')],
            ['value' => 'fa-solid fa-warehouse', 'label' => __('Warehouse')],
            // Misc
            ['value' => 'fa-solid fa-lock', 'label' => __('Lock / Exclusive')],
            ['value' => 'fa-solid fa-shield-halved', 'label' => __('Shield')],
            ['value' => 'fa-solid fa-eye', 'label' => __('Eye / Viewed')],
            ['value' => 'fa-solid fa-users', 'label' => __('Users / Popular')],
            ['value' => 'fa-solid fa-user-check', 'label' => __('Verified')],
            ['value' => 'fa-solid fa-comment-dots', 'label' => __('Reviews')],
            ['value' => 'fa-solid fa-ban', 'label' => __('Out of Stock')],
            ['value' => 'fa-solid fa-rotate', 'label' => __('Refresh / Return')],
        ];
    }
}
