<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Model;

use Panth\SmartBadge\Model\ResourceModel\Rule\CollectionFactory as RuleCollectionFactory;
use Panth\SmartBadge\Model\ConditionEvaluator;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;

class BadgeService
{
    private $ruleCollectionFactory;
    private $conditionEvaluator;
    private $categoryRepository;
    private $storeManager;
    private $categoryParentCache = [];

    public function __construct(
        RuleCollectionFactory $ruleCollectionFactory,
        ConditionEvaluator $conditionEvaluator,
        CategoryRepositoryInterface $categoryRepository,
        StoreManagerInterface $storeManager
    ) {
        $this->ruleCollectionFactory = $ruleCollectionFactory;
        $this->conditionEvaluator = $conditionEvaluator;
        $this->categoryRepository = $categoryRepository;
        $this->storeManager = $storeManager;
    }

    /**
     * Get all matching rule-based badges for a product
     * Returns array of all matching rule badges sorted by priority (highest first)
     *
     * @param ProductInterface $product
     * @return array
     */
    public function getRuleBadgesForProduct($product): array
    {
        if (!$product || !$product->getId()) {
            return [];
        }

        $badges = [];

        // Get active rules ordered by priority (highest first)
        $ruleCollection = $this->ruleCollectionFactory->create();
        $ruleCollection->addFieldToFilter('is_active', 1)
            ->setOrder('priority', 'DESC');

        $productId = (int)$product->getId();
        $productCategoryIds = $product->getCategoryIds() ?: [];

        foreach ($ruleCollection as $rule) {
            // Check if schedule is active
            if (!$this->conditionEvaluator->isScheduleActive($rule)) {
                continue;
            }

            // Check if product matches rule
            if (!$this->doesRuleMatchProduct($rule, $productId, $productCategoryIds)) {
                continue;
            }

            // Check smart conditions
            $smartConditions = $rule->getData('smart_conditions');
            if (!$this->conditionEvaluator->evaluateConditions($product, $smartConditions)) {
                continue;
            }

            $badges[] = $this->formatRuleBadge($rule);
        }

        return $badges;
    }

    /**
     * Get badge from rules for a product
     * Returns the highest priority rule badge that matches the product
     *
     * @deprecated Use getRuleBadgesForProduct() instead for multiple badge support
     * @param ProductInterface $product
     * @return array|null
     */
    public function getRuleBadgeForProduct($product): ?array
    {
        $badges = $this->getRuleBadgesForProduct($product);
        return !empty($badges) ? $badges[0] : null;
    }

    /**
     * Check if rule matches the product
     *
     * @param Rule $rule
     * @param int $productId
     * @param array $productCategoryIds
     * @return bool
     */
    private function doesRuleMatchProduct($rule, int $productId, array $productCategoryIds): bool
    {
        $ruleProductIds = $rule->getProductIds();
        $ruleCategoryIds = $rule->getCategoryIds();

        // If rule has specific product IDs, product must be in the list
        if (!empty($ruleProductIds)) {
            return in_array($productId, $ruleProductIds);
        }

        // If rule has specific category IDs, product must be in one of those categories
        if (!empty($ruleCategoryIds)) {
            if (!empty($productCategoryIds)) {
                // Direct category match
                $intersection = array_intersect($ruleCategoryIds, array_map('intval', $productCategoryIds));
                if (!empty($intersection)) {
                    return true;
                }

                // Check parent categories (child categories inherit parent rule badges)
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

        // No product_ids and no category_ids = rule applies to ALL products
        // (smart conditions will further filter)
        return true;
    }

    /**
     * Get all parent category IDs for a given category
     *
     * @param int $categoryId
     * @return array
     */
    private function getAllParentCategoryIds(int $categoryId): array
    {
        // Check cache first
        if (isset($this->categoryParentCache[$categoryId])) {
            return $this->categoryParentCache[$categoryId];
        }

        $parentIds = [];

        try {
            $category = $this->categoryRepository->get($categoryId);
            $pathIds = explode('/', $category->getPath());

            // Remove the category itself and root category (ID 1)
            $parentIds = array_filter(
                array_map('intval', $pathIds),
                function ($id) use ($categoryId) {
                    return $id > 1 && $id !== $categoryId;
                }
            );

            $parentIds = array_values($parentIds);
        } catch (\Exception $e) {
            // If category can't be loaded, return empty array
            $parentIds = [];
        }

        // Cache the result
        $this->categoryParentCache[$categoryId] = $parentIds;

        return $parentIds;
    }

    /**
     * Format rule badge data for display
     *
     * @param Rule $rule
     * @return array
     */
    private function formatRuleBadge($rule): array
    {
        $badgeData = [
            'type' => $rule->getData('badge_type') ?: 'custom',
            'label' => $rule->getData('badge_text') ?: 'NEW',
            'class' => 'badge-' . ($rule->getData('badge_type') ?: 'custom'),
            'priority' => (int)$rule->getPriority()
        ];

        // Get custom icon from rule (can be emoji or FontAwesome class)
        $customIcon = $rule->getData('badge_icon');
        if ($customIcon) {
            $badgeData['icon'] = $customIcon;
        } else {
            // Fallback to icon based on type
            $badgeData['icon'] = $this->getIconForType($rule->getData('badge_type'));
        }

        // Check for badge image — build full media URL
        $badgeImage = $rule->getData('badge_image');
        if ($badgeImage) {
            try {
                $mediaUrl = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
                $badgeData['image'] = $mediaUrl . 'smartbadge/' . $badgeImage;
            } catch (\Exception $e) {
                $badgeData['image'] = $badgeImage;
            }
        }

        // Handle custom colors
        $bgColor = $rule->getData('badge_color');
        if ($bgColor && $this->isValidHexColor($bgColor)) {
            $badgeData['customColor'] = $bgColor;
        } else {
            // Use CSS variable for standard types
            $badgeData['cssVar'] = $this->getCssVarForType($rule->getData('badge_type'));
        }

        // Add animation if set
        $animation = $rule->getData('animation');
        if ($animation) {
            $badgeData['animation'] = $animation;
        }

        // Add display location settings
        $badgeData['display_on'] = $rule->getData('display_on') ?: 'all';

        // Add position settings
        $useSamePosition = (bool)$rule->getData('use_same_position');
        $badgeData['use_same_position'] = $useSamePosition;

        if ($useSamePosition) {
            $badgeData['position'] = $rule->getData('position_all') ?: 'top-left';
        } else {
            $badgeData['position_category'] = $rule->getData('position_category') ?: 'top-left';
            $badgeData['position_product'] = $rule->getData('position_product') ?: 'top-left';
            $badgeData['position_slider'] = $rule->getData('position_slider') ?: 'top-left';
        }

        // Add Advanced CSS customization (badge_style JSON)
        $badgeStyle = $rule->getData('badge_style');
        if ($badgeStyle) {
            // Decode if it's a JSON string
            if (is_string($badgeStyle)) {
                $badgeStyle = json_decode($badgeStyle, true);
            }
            if (is_array($badgeStyle) && !empty($badgeStyle)) {
                $badgeData['badge_style'] = $badgeStyle;
            }
        }

        // Add image settings
        $imageSettings = $rule->getData('image_settings');
        if ($imageSettings) {
            if (is_string($imageSettings)) {
                $imageSettings = json_decode($imageSettings, true);
            }
            if (is_array($imageSettings) && !empty($imageSettings)) {
                $badgeData['image_settings'] = $imageSettings;
            }
        }

        return $badgeData;
    }

    /**
     * Get icon for badge type
     *
     * @param string|null $type
     * @return string
     */
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

    /**
     * Get CSS variable for badge type
     *
     * @param string|null $type
     * @return string
     */
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

    /**
     * Validate hex color
     *
     * @param string $color
     * @return bool
     */
    private function isValidHexColor($color): bool
    {
        return (bool)preg_match('/^#[a-f0-9]{6}$/i', $color);
    }
}
