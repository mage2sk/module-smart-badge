<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Store\Model\ScopeInterface;
use Panth\SmartBadge\Model\BadgeService;

class BadgeHelper extends AbstractHelper
{
    public const DEFAULT_MAX_BADGES = 1;

    public const DEFAULT_PRIORITY_ORDER = ['sale', 'new', 'stock', 'custom'];

    private const SOURCE_MANUAL = 100;
    private const SOURCE_RULE = 50;
    private const SOURCE_AUTO = 10;

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
        return $this->scopeConfig->isSetFlag('smart_badge/general/enabled', ScopeInterface::SCOPE_STORE);
    }

    public function getProductBadges($product, bool $limit = true): array
    {
        if (!$this->isEnabled()) {
            return [];
        }

        if ($this->getCombinationMode() === 'collect_all') {
            $badges = $this->getBadgesCollectAllMode($product);
        } else {
            $badges = $this->getBadgesPriorityMode($product);
        }

        $badges = $this->sortByPriorityOrder($badges);
        if ($limit) {
            $badges = array_slice($badges, 0, $this->getMaxBadges());
        }

        return $this->applyStyleSettings($this->applyIconSetting($badges));
    }

    public static function getTone(string $type): string
    {
        $type = strtolower($type);
        if ($type === 'sale') {
            return 'sale';
        }
        if ($type === 'new') {
            return 'new';
        }
        if ($type === 'stock' || $type === 'limited') {
            return 'stock';
        }
        return 'custom';
    }

    public function getPriorityOrder(): array
    {
        $value = (string)$this->scopeConfig->getValue(
            'smart_badge/general/priority_order',
            ScopeInterface::SCOPE_STORE
        );
        $order = [];
        foreach (explode(',', strtolower($value)) as $tone) {
            $tone = trim($tone);
            if (in_array($tone, self::DEFAULT_PRIORITY_ORDER, true) && !in_array($tone, $order, true)) {
                $order[] = $tone;
            }
        }
        foreach (self::DEFAULT_PRIORITY_ORDER as $tone) {
            if (!in_array($tone, $order, true)) {
                $order[] = $tone;
            }
        }
        return $order;
    }

    public function isShowIcons(): bool
    {
        return $this->scopeConfig->isSetFlag('smart_badge/display/show_icons', ScopeInterface::SCOPE_STORE);
    }

    public function isUseCustomStyling(): bool
    {
        return $this->scopeConfig->isSetFlag(
            'smart_badge/display/use_custom_styling',
            ScopeInterface::SCOPE_STORE
        );
    }

    private function getBadgesPriorityMode($product): array
    {
        $badges = [];

        $manual = $this->getProductManualBadge($product);
        if ($manual) {
            $badges[] = $manual;
        }

        foreach ($this->badgeService->getRuleBadgesForProduct($product) as $ruleBadge) {
            $badges[] = $this->markRuleBadge($ruleBadge);
        }

        foreach ($this->getAutoBadges($product) as $autoBadge) {
            $autoBadge['priority'] = self::SOURCE_AUTO;
            $badges[] = $autoBadge;
        }

        return $badges;
    }

    private function getBadgesCollectAllMode($product): array
    {
        $badges = [];
        $showMultipleRules = $this->shouldShowMultipleRuleBadges();
        $showAutoWithManual = $this->shouldShowAutoBadgesWithManual();

        $manual = $this->getProductManualBadge($product);
        if ($manual) {
            $badges[] = $manual;
        }

        $ruleBadges = $this->badgeService->getRuleBadgesForProduct($product);
        if (!empty($ruleBadges)) {
            if (!$showMultipleRules) {
                $ruleBadges = [$ruleBadges[0]];
            }
            foreach ($ruleBadges as $ruleBadge) {
                $badges[] = $this->markRuleBadge($ruleBadge);
            }
        }

        if ($showAutoWithManual || empty($badges)) {
            foreach ($this->getAutoBadges($product) as $autoBadge) {
                $autoBadge['priority'] = self::SOURCE_AUTO;
                $badges[] = $autoBadge;
            }
        }

        return $badges;
    }

    private function getProductManualBadge($product): ?array
    {
        $manualBadge = $product->getData('product_badge');
        if (!$manualBadge) {
            return null;
        }
        $badge = $this->getManualBadge((string)$manualBadge);
        if (!$badge) {
            return null;
        }

        $customText = $product->getData('badge_custom_text');
        if ($customText) {
            $badge['label'] = $customText;
        }

        $customColor = $product->getData('badge_custom_color');
        if ($customColor && $this->isValidHexColor($customColor)) {
            $badge['customColor'] = $customColor;
            unset($badge['cssVar']);
        }

        $badge['priority'] = self::SOURCE_MANUAL;
        return $badge;
    }

    private function markRuleBadge(array $ruleBadge): array
    {
        $ruleBadge['rule_priority'] = (int)($ruleBadge['priority'] ?? 0);
        $ruleBadge['priority'] = self::SOURCE_RULE;
        return $ruleBadge;
    }

    private function sortByPriorityOrder(array $badges): array
    {
        $rank = array_flip($this->getPriorityOrder());
        $indexed = [];
        foreach (array_values($badges) as $index => $badge) {
            $badge['tone'] = self::getTone((string)($badge['type'] ?? ''));
            $indexed[] = [$badge, $index];
        }

        usort($indexed, function (array $a, array $b) use ($rank): int {
            $toneCompare = $rank[$a[0]['tone']] <=> $rank[$b[0]['tone']];
            if ($toneCompare !== 0) {
                return $toneCompare;
            }
            $sourceCompare = ($b[0]['priority'] ?? 0) <=> ($a[0]['priority'] ?? 0);
            if ($sourceCompare !== 0) {
                return $sourceCompare;
            }
            $ruleCompare = ($b[0]['rule_priority'] ?? 0) <=> ($a[0]['rule_priority'] ?? 0);
            if ($ruleCompare !== 0) {
                return $ruleCompare;
            }
            return $a[1] <=> $b[1];
        });

        return array_column($indexed, 0);
    }

    private function applyIconSetting(array $badges): array
    {
        if ($this->isShowIcons()) {
            return $badges;
        }
        foreach ($badges as $key => $badge) {
            unset($badges[$key]['icon']);
        }
        return $badges;
    }

    private function applyStyleSettings(array $badges): array
    {
        if ($this->isUseCustomStyling()) {
            return $badges;
        }
        foreach ($badges as $key => $badge) {
            unset($badges[$key]['customColor'], $badges[$key]['badge_style'], $badges[$key]['animation']);
        }
        return $badges;
    }

    public function getMaxBadges(): int
    {
        $max = (int)$this->scopeConfig->getValue('smart_badge/general/max_badges', ScopeInterface::SCOPE_STORE);
        return ($max > 0 && $max <= 10) ? $max : self::DEFAULT_MAX_BADGES;
    }

    private function getCombinationMode(): string
    {
        return $this->scopeConfig->getValue(
            'smart_badge/general/badge_combination_mode',
            ScopeInterface::SCOPE_STORE
        ) ?: 'priority';
    }

    private function shouldShowMultipleRuleBadges(): bool
    {
        return $this->scopeConfig->isSetFlag(
            'smart_badge/general/show_multiple_rule_badges',
            ScopeInterface::SCOPE_STORE
        );
    }

    private function shouldShowAutoBadgesWithManual(): bool
    {
        return $this->scopeConfig->isSetFlag(
            'smart_badge/general/auto_badges_with_manual',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getBadgeLayout(): string
    {
        return $this->scopeConfig->getValue(
            'smart_badge/display/badge_layout',
            ScopeInterface::SCOPE_STORE
        ) ?: 'vertical';
    }

    public function getBadgeSpacing(): string
    {
        return $this->scopeConfig->getValue(
            'smart_badge/display/badge_spacing',
            ScopeInterface::SCOPE_STORE
        ) ?: 'gap-2';
    }

    private function isValidHexColor($color): bool
    {
        return (bool)preg_match('/^#[a-f0-9]{6}$/i', $color);
    }

    private function getManualBadge(string $type): array
    {
        $badgeMap = [
            'new' => ['label' => 'New', 'icon' => 'fa-solid fa-star', 'cssVar' => '--badge-new'],
            'sale' => ['label' => 'Sale', 'icon' => 'fa-solid fa-tag', 'cssVar' => '--badge-sale'],
            'hot' => ['label' => 'Hot', 'icon' => 'fa-solid fa-fire', 'cssVar' => '--badge-hot'],
            'limited' => ['label' => 'Limited', 'icon' => 'fa-solid fa-clock', 'cssVar' => '--badge-limited'],
            'bestseller' => ['label' => 'Bestseller', 'icon' => 'fa-solid fa-trophy', 'cssVar' => '--badge-sale'],
            'trending' => ['label' => 'Trending', 'icon' => 'fa-solid fa-arrow-trend-up', 'cssVar' => '--badge-hot'],
            'exclusive' => ['label' => 'Exclusive', 'icon' => 'fa-solid fa-gem', 'cssVar' => '--badge-hot'],
            'featured' => ['label' => 'Featured', 'icon' => 'fa-solid fa-star', 'cssVar' => '--badge-new'],
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
                'icon' => 'fa-solid fa-tag'
            ];
        }

        if ($this->isNewProduct($product)) {
            $badges[] = [
                'type' => 'new',
                'label' => 'New',
                'class' => 'badge-new',
                'cssVar' => '--badge-new',
                'icon' => 'fa-solid fa-star'
            ];
        }

        if ($this->isLowStock($product)) {
            $qty = $this->getStockQty($product);
            $badges[] = [
                'type' => 'stock',
                'label' => 'Only ' . (int)$qty . ' left',
                'class' => 'badge-limited',
                'cssVar' => '--badge-limited',
                'icon' => 'fa-solid fa-bolt'
            ];
        }

        return $badges;
    }

    private function isNewProduct($product): bool
    {
        $newsFrom = $product->getData('news_from_date');
        $newsTo = $product->getData('news_to_date');
        if ($newsFrom || $newsTo) {
            return $this->timezone->isScopeDateInInterval($product->getStore(), $newsFrom, $newsTo);
        }

        $createdAt = strtotime((string)$product->getCreatedAt());
        if ($createdAt === false) {
            return false;
        }
        $daysSinceCreation = (time() - $createdAt) / (60 * 60 * 24);
        return $daysSinceCreation <= 30;
    }

    private function isOnSale($product): bool
    {
        return $this->getDiscountPercent($product) >= 1;
    }

    private function getDiscountPercent($product): int
    {
        $amounts = $this->getPriceAmounts($product);
        if ($amounts !== null) {
            [$regularPrice, $finalPrice] = $amounts;
        } else {
            $specialPrice = $product->getSpecialPrice();
            $regularPrice = (float)$product->getPrice();
            if (!$specialPrice || (float)$specialPrice >= $regularPrice) {
                return 0;
            }
            if (!$this->timezone->isScopeDateInInterval(
                $product->getStore(),
                $product->getSpecialFromDate(),
                $product->getSpecialToDate()
            )) {
                return 0;
            }
            $finalPrice = (float)$specialPrice;
        }

        if ($regularPrice > 0 && $finalPrice < $regularPrice) {
            return (int)round((($regularPrice - $finalPrice) / $regularPrice) * 100);
        }

        return 0;
    }

    private function getPriceAmounts($product): ?array
    {
        if (!is_object($product) || !method_exists($product, 'getPriceInfo')) {
            return null;
        }

        try {
            $priceInfo = $product->getPriceInfo();
            $regularPrice = $priceInfo->getPrice('regular_price');
            $regularAmount = method_exists($regularPrice, 'getMinRegularAmount')
                ? $regularPrice->getMinRegularAmount()
                : $regularPrice->getAmount();
            $finalPrice = $priceInfo->getPrice('final_price');
            $finalAmount = method_exists($finalPrice, 'getMinimalPrice')
                ? $finalPrice->getMinimalPrice()
                : $finalPrice->getAmount();
            if (!$regularAmount || !$finalAmount) {
                return null;
            }
            return [(float)$regularAmount->getValue(), (float)$finalAmount->getValue()];
        } catch (\Exception $e) {
            return null;
        }
    }

    private function isLowStock($product): bool
    {
        try {
            $stockItem = $this->stockRegistry->getStockItem($product->getId());
            if (!$stockItem->getIsInStock()) {
                return false;
            }
            $qty = (float)$stockItem->getQty();
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
