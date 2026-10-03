<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Model;

use Panth\SmartBadge\Model\ResourceModel\Rule\CollectionFactory as RuleCollectionFactory;
use Panth\SmartBadge\Model\ConditionEvaluator;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Http\Context as HttpContext;

class BadgeService
{
    private $ruleCollectionFactory;
    private $conditionEvaluator;
    private $categoryRepository;
    private $storeManager;
    private $httpContext;
    private $categoryParentCache = [];
    private $activeRules = null;

    private const POSITIONS = [
        'top-left', 'top-right', 'bottom-left', 'bottom-right',
        'top-center', 'bottom-center', 'center-left', 'center-right'
    ];

    private const BORDER_STYLES = ['none', 'solid', 'dashed', 'dotted', 'double', 'groove', 'ridge'];

    private const SIZE_UNITS = ['px', '%', 'em', 'rem'];

    private const SHADOW_PRESETS = ['none', 'default', 'sm', 'md', 'lg', 'xl', 'inner', 'glow'];

    public function __construct(
        RuleCollectionFactory $ruleCollectionFactory,
        ConditionEvaluator $conditionEvaluator,
        CategoryRepositoryInterface $categoryRepository,
        StoreManagerInterface $storeManager,
        HttpContext $httpContext
    ) {
        $this->ruleCollectionFactory = $ruleCollectionFactory;
        $this->conditionEvaluator = $conditionEvaluator;
        $this->categoryRepository = $categoryRepository;
        $this->storeManager = $storeManager;
        $this->httpContext = $httpContext;
    }

    public function getRuleBadgesForProduct($product): array
    {
        if (!$product || !$product->getId()) {
            return [];
        }

        $badges = [];

        $productId = (int)$product->getId();
        $productCategoryIds = $product->getCategoryIds() ?: [];

        foreach ($this->getActiveRules() as $rule) {
            if (!$this->conditionEvaluator->isScheduleActive($rule)) {
                continue;
            }

            if (!$this->doesRuleMatchProduct($rule, $productId, $productCategoryIds)) {
                continue;
            }

            $smartConditions = $rule->getData('smart_conditions');
            if (!$this->conditionEvaluator->evaluateConditions($product, $smartConditions)) {
                continue;
            }

            $badges[] = $this->formatRuleBadge($rule);
        }

        return $badges;
    }

    private function getActiveRules(): array
    {
        if ($this->activeRules === null) {
            $ruleCollection = $this->ruleCollectionFactory->create();
            $ruleCollection->addFieldToFilter('is_active', 1)
                ->setOrder('priority', 'DESC')
                ->setOrder('rule_id', 'ASC');
            $this->activeRules = array_values($ruleCollection->getItems());
        }

        $storeId = (int)$this->storeManager->getStore()->getId();
        $customerGroupId = (int)$this->httpContext->getValue('customer_group');

        return array_values(array_filter(
            $this->activeRules,
            function ($rule) use ($storeId, $customerGroupId) {
                return $this->isRuleInScope($rule, $storeId, $customerGroupId);
            }
        ));
    }

    private function isRuleInScope($rule, int $storeId, int $customerGroupId): bool
    {
        $storeIds = $rule->getStoreIds();
        if (!empty($storeIds) && !in_array(0, $storeIds, true) && !in_array($storeId, $storeIds, true)) {
            return false;
        }

        $customerGroupIds = $rule->getCustomerGroupIds();
        if (!empty($customerGroupIds) && !in_array($customerGroupId, $customerGroupIds, true)) {
            return false;
        }

        return true;
    }

    public function getRuleBadgeForProduct($product): ?array
    {
        $badges = $this->getRuleBadgesForProduct($product);
        return !empty($badges) ? $badges[0] : null;
    }

    private function doesRuleMatchProduct($rule, int $productId, array $productCategoryIds): bool
    {
        $ruleProductIds = $rule->getProductIds();
        $ruleCategoryIds = $rule->getCategoryIds();

        if (!empty($ruleProductIds)) {
            return in_array($productId, $ruleProductIds);
        }

        if (!empty($ruleCategoryIds)) {
            if (!empty($productCategoryIds)) {
                $intersection = array_intersect($ruleCategoryIds, array_map('intval', $productCategoryIds));
                if (!empty($intersection)) {
                    return true;
                }

                foreach ($productCategoryIds as $productCategoryId) {
                    $parentIds = $this->getAllParentCategoryIds((int)$productCategoryId);
                    $parentIntersection = array_intersect($ruleCategoryIds, $parentIds);
                    if (!empty($parentIntersection)) {
                        return true;
                    }
                }
            }
            return false;
        }

        return true;
    }

    private function getAllParentCategoryIds(int $categoryId): array
    {
        if (isset($this->categoryParentCache[$categoryId])) {
            return $this->categoryParentCache[$categoryId];
        }

        $parentIds = [];

        try {
            $category = $this->categoryRepository->get($categoryId);
            $pathIds = explode('/', $category->getPath());

            $parentIds = array_filter(
                array_map('intval', $pathIds),
                function ($id) use ($categoryId) {
                    return $id > 1 && $id !== $categoryId;
                }
            );

            $parentIds = array_values($parentIds);
        } catch (\Exception $e) {
            $parentIds = [];
        }

        $this->categoryParentCache[$categoryId] = $parentIds;

        return $parentIds;
    }

    private function formatRuleBadge($rule): array
    {
        $badgeType = (string)$rule->getData('badge_type');
        if (!preg_match('/^[a-z0-9_-]{1,50}$/i', $badgeType)) {
            $badgeType = 'custom';
        }

        $badgeData = [
            'type' => $badgeType,
            'label' => (string)($rule->getData('badge_text') ?: 'NEW'),
            'class' => 'badge-' . $badgeType,
            'priority' => (int)$rule->getPriority()
        ];

        $customIcon = trim((string)$rule->getData('badge_icon'));
        if ($customIcon !== '' && strpos($customIcon, 'fa') === 0
            && !preg_match('/^[a-z0-9 _-]+$/i', $customIcon)
        ) {
            $customIcon = '';
        }
        if ($customIcon !== '') {
            $badgeData['icon'] = $customIcon;
        } else {
            $badgeData['icon'] = $this->getIconForType($badgeType);
        }

        $badgeImage = (string)$rule->getData('badge_image');
        if ($badgeImage !== ''
            && preg_match('/^[a-z0-9][a-z0-9._-]*\.(jpe?g|png|gif|webp|svg)$/i', $badgeImage)
        ) {
            try {
                $mediaUrl = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
                $badgeData['image'] = $mediaUrl . 'smartbadge/' . $badgeImage;
            } catch (\Exception $e) {
                unset($badgeData['image']);
            }
        }

        $bgColor = $rule->getData('badge_color');
        if ($bgColor && $this->isValidHexColor($bgColor)) {
            $badgeData['customColor'] = $bgColor;
        } else {
            $badgeData['cssVar'] = $this->getCssVarForType($rule->getData('badge_type'));
        }

        $animation = (string)$rule->getData('animation');
        if ($animation !== '' && $animation !== 'none' && preg_match('/^[a-z][a-z0-9]{0,49}$/i', $animation)) {
            $badgeData['animation'] = $animation;
        }

        $displayOn = (string)$rule->getData('display_on');
        $badgeData['display_on'] = preg_match('/^[a-z_]{1,50}$/', $displayOn) ? $displayOn : 'all';

        $useSamePosition = (bool)$rule->getData('use_same_position');
        $badgeData['use_same_position'] = $useSamePosition;

        if ($useSamePosition) {
            $badgeData['position'] = $this->normalizePosition($rule->getData('position_all'));
        } else {
            $badgeData['position_category'] = $this->normalizePosition($rule->getData('position_category'));
            $badgeData['position_product'] = $this->normalizePosition($rule->getData('position_product'));
            $badgeData['position_slider'] = $this->normalizePosition($rule->getData('position_slider'));
        }

        $badgeStyle = $rule->getData('badge_style');
        if ($badgeStyle) {
            if (is_string($badgeStyle)) {
                $badgeStyle = json_decode($badgeStyle, true);
            }
            if (is_array($badgeStyle)) {
                $badgeStyle = $this->sanitizeBadgeStyle($badgeStyle);
                if (!empty($badgeStyle)) {
                    $badgeData['badge_style'] = $badgeStyle;
                }
            }
        }

        $imageSettings = $rule->getData('image_settings');
        if ($imageSettings) {
            if (is_string($imageSettings)) {
                $imageSettings = json_decode($imageSettings, true);
            }
            if (is_array($imageSettings) && !empty($imageSettings['imageOnly'])
                && $imageSettings['imageOnly'] !== 'false'
            ) {
                $badgeData['imageOnly'] = true;
            }
        }

        return $badgeData;
    }

    private function normalizePosition($position): string
    {
        return in_array($position, self::POSITIONS, true) ? $position : 'top-left';
    }

    private function sanitizeBadgeStyle(array $style): array
    {
        $clean = [];

        foreach (['width', 'height', 'fontSize'] as $key) {
            if (!isset($style[$key])) {
                continue;
            }
            $value = is_array($style[$key]) ? ($style[$key]['value'] ?? '') : $style[$key];
            $unit = is_array($style[$key]) ? ($style[$key]['unit'] ?? 'px') : 'px';
            if (is_numeric($value) && (float)$value > 0 && in_array($unit, self::SIZE_UNITS, true)) {
                $clean[$key] = ['value' => (string)(float)$value, 'unit' => $unit];
            }
        }

        foreach (['borderRadius', 'borderWidth', 'opacity'] as $key) {
            if (isset($style[$key]) && is_numeric($style[$key])) {
                $clean[$key] = (string)(float)$style[$key];
            }
        }

        if (isset($style['fontWeight']) && preg_match('/^([1-9]00|normal|bold)$/', (string)$style['fontWeight'])) {
            $clean['fontWeight'] = (string)$style['fontWeight'];
        }

        if (isset($style['borderStyle']) && in_array($style['borderStyle'], self::BORDER_STYLES, true)) {
            $clean['borderStyle'] = $style['borderStyle'];
        }

        if (isset($style['borderColor']) && is_string($style['borderColor'])
            && preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $style['borderColor'])
        ) {
            $clean['borderColor'] = $style['borderColor'];
        }

        if (isset($style['padding']) && is_array($style['padding'])) {
            foreach (['top', 'right', 'bottom', 'left'] as $side) {
                if (isset($style['padding'][$side]) && is_numeric($style['padding'][$side])) {
                    $clean['padding'][$side] = (string)(float)$style['padding'][$side];
                }
            }
        }

        if (isset($style['boxShadow'])) {
            if (is_string($style['boxShadow']) && in_array($style['boxShadow'], self::SHADOW_PRESETS, true)) {
                $clean['boxShadow'] = $style['boxShadow'];
            } elseif (is_array($style['boxShadow']) && !empty($style['boxShadow']['enabled'])) {
                $shadow = ['enabled' => true];
                foreach (['x', 'y', 'blur', 'opacity'] as $key) {
                    if (isset($style['boxShadow'][$key]) && is_numeric($style['boxShadow'][$key])) {
                        $shadow[$key] = (float)$style['boxShadow'][$key];
                    }
                }
                $clean['boxShadow'] = $shadow;
            }
        }

        return $clean;
    }

    private function getIconForType(?string $type): string
    {
        $iconMap = [
            'new' => '✨',
            'sale' => '🔥',
            'hot' => '🔥',
            'limited' => '⏰',
            'bestseller' => '⭐',
            'trending' => '📈',
            'exclusive' => '💎',
            'featured' => '✨',
        ];

        return $iconMap[$type] ?? '🏷️';
    }

    private function getCssVarForType(?string $type): string
    {
        $cssVarMap = [
            'new' => '--badge-new',
            'sale' => '--badge-sale',
            'hot' => '--badge-hot',
            'limited' => '--badge-limited',
            'bestseller' => '--badge-sale',
            'trending' => '--badge-hot',
            'exclusive' => '--badge-hot',
            'featured' => '--badge-new',
        ];

        return $cssVarMap[$type] ?? '--badge-new';
    }

    private function isValidHexColor($color): bool
    {
        return (bool)preg_match('/^#[a-f0-9]{6}$/i', $color);
    }
}
