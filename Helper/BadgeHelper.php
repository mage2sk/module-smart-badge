<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Panth\SmartBadge\Model\BadgeService;

class BadgeHelper extends AbstractHelper
{
    private $timezone;
    private $stockRegistry;
    private $badgeService;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        TimezoneInterface $timezone,
        StockRegistryInterface $stockRegistry,
        BadgeService $badgeService
    ) {
        parent::__construct($context);
        $this->timezone = $timezone;
        $this->stockRegistry = $stockRegistry;
        $this->badgeService = $badgeService;
    }

    public function isEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag('smart_badge/general/enabled');
    }

    public function getProductBadges($product): array
    {
        if (!$this->isEnabled()) {
            return [];
        }

        $maxBadges = $this->getMaxBadges();
        $combinationMode = $this->getCombinationMode();

        if ($combinationMode === 'priority') {
            return $this->getBadgesPriorityMode($product, $maxBadges);
        }

        return $this->getBadgesCollectAllMode($product, $maxBadges);
    }

    private function getBadgesPriorityMode($product, int $maxBadges): array
    {
        $badges = [];

        $manualBadge = $product->getData('product_badge');
        if ($manualBadge && $manualBadge !== '') {
            $badge = $this->getManualBadge($manualBadge);

            $customText = $product->getData('badge_custom_text');
            if ($customText) {
                $badge['label'] = $customText;
            }

            $customColor = $product->getData('badge_custom_color');
            if ($customColor && $this->isValidHexColor($customColor)) {
                $badge['customColor'] = $customColor;
                unset($badge['cssVar']);
            }

            $badges[] = $badge;
            return array_slice($badges, 0, $maxBadges);
        }

        $ruleBadges = $this->badgeService->getRuleBadgesForProduct($product);
        if (!empty($ruleBadges)) {
            return [array_shift($ruleBadges)];
        }

        $badges = $this->getAutoBadges($product);

        return array_slice($badges, 0, $maxBadges);
    }

    private function getBadgesCollectAllMode($product, int $maxBadges): array
    {
        $badges = [];
        $showMultipleRules = $this->shouldShowMultipleRuleBadges();
        $showAutoWithManual = $this->shouldShowAutoBadgesWithManual();

        $manualBadge = $product->getData('product_badge');
        if ($manualBadge && $manualBadge !== '') {
            $badge = $this->getManualBadge($manualBadge);

            $customText = $product->getData('badge_custom_text');
            if ($customText) {
                $badge['label'] = $customText;
            }

            $customColor = $product->getData('badge_custom_color');
            if ($customColor && $this->isValidHexColor($customColor)) {
                $badge['customColor'] = $customColor;
                unset($badge['cssVar']);
            }

            $badge['priority'] = 100;
            $badges[] = $badge;
        }

        $ruleBadges = $this->badgeService->getRuleBadgesForProduct($product);
        if (!empty($ruleBadges)) {
            if ($showMultipleRules) {
                foreach ($ruleBadges as $ruleBadge) {
                    $ruleBadge['priority'] = 50;
                    $badges[] = $ruleBadge;
                }
            } else {
                $ruleBadges[0]['priority'] = 50;
                $badges[] = $ruleBadges[0];
            }
        }

        if ($showAutoWithManual || empty($badges)) {
            $autoBadges = $this->getAutoBadges($product);
            foreach ($autoBadges as $autoBadge) {
                $autoBadge['priority'] = 10;
                $badges[] = $autoBadge;
            }
        }

        usort($badges, function($a, $b) {
            return ($b['priority'] ?? 0) - ($a['priority'] ?? 0);
        });

        return array_slice($badges, 0, $maxBadges);
    }

    private function getMaxBadges(): int
    {
        $max = (int)$this->scopeConfig->getValue('smart_badge/general/max_badges');
        return ($max > 0 && $max <= 10) ? $max : 3;
    }

    private function getCombinationMode(): string
    {
        return $this->scopeConfig->getValue('smart_badge/general/badge_combination_mode') ?: 'priority';
    }

    private function shouldShowMultipleRuleBadges(): bool
    {
        return $this->scopeConfig->isSetFlag('smart_badge/general/show_multiple_rule_badges');
    }

    private function shouldShowAutoBadgesWithManual(): bool
    {
        return $this->scopeConfig->isSetFlag('smart_badge/general/auto_badges_with_manual');
    }

    public function getBadgeLayout(): string
    {
        return $this->scopeConfig->getValue('smart_badge/display/badge_layout') ?: 'vertical';
    }

    public function getBadgeSpacing(): string
    {
        return $this->scopeConfig->getValue('smart_badge/display/badge_spacing') ?: 'gap-2';
    }

    private function isValidHexColor($color): bool
    {
        return (bool)preg_match('/^#[a-f0-9]{6}$/i', $color);
    }

    private function getManualBadge(string $type): array
    {
        $badgeMap = [
            'new' => ['label' => 'NEW', 'icon' => '🆕', 'cssVar' => '--badge-new'],
            'sale' => ['label' => 'SALE', 'icon' => '🔥', 'cssVar' => '--badge-sale'],
            'hot' => ['label' => 'HOT', 'icon' => '🔥', 'cssVar' => '--badge-hot'],
            'limited' => ['label' => 'LIMITED', 'icon' => '⏰', 'cssVar' => '--badge-limited'],
            'bestseller' => ['label' => 'BESTSELLER', 'icon' => '⭐', 'cssVar' => '--badge-sale'],
            'trending' => ['label' => 'TRENDING', 'icon' => '📈', 'cssVar' => '--badge-hot'],
            'exclusive' => ['label' => 'EXCLUSIVE', 'icon' => '💎', 'cssVar' => '--badge-hot'],
            'featured' => ['label' => 'FEATURED', 'icon' => '✨', 'cssVar' => '--badge-new'],
        ];

        if (isset($badgeMap[$type])) {
            return [
                'type' => $type,
                'label' => $badgeMap[$type]['label'],
                'class' => 'badge-' . $type,
                'cssVar' => $badgeMap[$type]['cssVar'],
                'icon' => $badgeMap[$type]['icon']
            ];
        }

        return [];
    }

    private function getAutoBadges($product): array
    {
        $badges = [];

        if ($this->isOnSale($product)) {
            $discount = $this->getDiscountPercent($product);
            $badges[] = [
                'type' => 'sale',
                'label' => '-' . $discount . '%',
                'class' => 'badge-sale',
                'cssVar' => '--badge-sale',
                'icon' => '🔥'
            ];
        }

        if ($this->isNewProduct($product)) {
            $badges[] = [
                'type' => 'new',
                'label' => 'NEW',
                'class' => 'badge-new',
                'cssVar' => '--badge-new',
                'icon' => '✨'
            ];
        }

        if ($this->isLowStock($product)) {
            $qty = $this->getStockQty($product);
            $badges[] = [
                'type' => 'stock',
                'label' => 'Only ' . (int)$qty . ' left',
                'class' => 'badge-limited',
                'cssVar' => '--badge-limited',
                'icon' => '⚡'
            ];
        }

        return $badges;
    }

    private function isNewProduct($product): bool
    {
        $createdAt = strtotime($product->getCreatedAt());
        $daysSinceCreation = (time() - $createdAt) / (60 * 60 * 24);
        return $daysSinceCreation <= 30;
    }

    private function isOnSale($product): bool
    {
        $specialPrice = $product->getSpecialPrice();
        $regularPrice = $product->getPrice();
        return $specialPrice && $specialPrice < $regularPrice;
    }

    private function getDiscountPercent($product): int
    {
        $regularPrice = (float)$product->getPrice();
        $specialPrice = (float)$product->getSpecialPrice();

        if ($regularPrice > 0 && $specialPrice < $regularPrice) {
            return (int)round((($regularPrice - $specialPrice) / $regularPrice) * 100);
        }

        return 0;
    }

    private function isLowStock($product): bool
    {
        try {
            $qty = $this->getStockQty($product);
            return $qty > 0 && $qty <= 10;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function getStockQty($product)
    {
        try {
            $stockItem = $this->stockRegistry->getStockItem($product->getId());
            return $stockItem->getQty();
        } catch (\Exception $e) {
            return 0;
        }
    }
}
