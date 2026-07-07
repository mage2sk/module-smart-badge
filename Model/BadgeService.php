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

    public function getRuleBadgesForProduct($product): array
    {
        if (!$product || !$product->getId()) {
            return [];
        }

        $badges = [];

        $ruleCollection = $this->ruleCollectionFactory->create();
        $ruleCollection->addFieldToFilter('is_active', 1)
            ->setOrder('priority', 'DESC');

        $productId = (int)$product->getId();
        $productCategoryIds = $product->getCategoryIds() ?: [];

        foreach ($ruleCollection as $rule) {
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
        $badgeData = [
            'type' => $rule->getData('badge_type') ?: 'custom',
            'label' => $rule->getData('badge_text') ?: 'NEW',
            'class' => 'badge-' . ($rule->getData('badge_type') ?: 'custom'),
            'priority' => (int)$rule->getPriority()
        ];

        $customIcon = $rule->getData('badge_icon');
        if ($customIcon) {
            $badgeData['icon'] = $customIcon;
        } else {
            $badgeData['icon'] = $this->getIconForType($rule->getData('badge_type'));
        }

        $badgeImage = $rule->getData('badge_image');
        if ($badgeImage) {
            try {
                $mediaUrl = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
                $badgeData['image'] = $mediaUrl . 'smartbadge/' . $badgeImage;
            } catch (\Exception $e) {
                $badgeData['image'] = $badgeImage;
            }
        }

        $bgColor = $rule->getData('badge_color');
        if ($bgColor && $this->isValidHexColor($bgColor)) {
            $badgeData['customColor'] = $bgColor;
        } else {
            $badgeData['cssVar'] = $this->getCssVarForType($rule->getData('badge_type'));
        }

        $animation = $rule->getData('animation');
        if ($animation) {
            $badgeData['animation'] = $animation;
        }

        $badgeData['display_on'] = $rule->getData('display_on') ?: 'all';

        $useSamePosition = (bool)$rule->getData('use_same_position');
        $badgeData['use_same_position'] = $useSamePosition;

        if ($useSamePosition) {
            $badgeData['position'] = $rule->getData('position_all') ?: 'top-left';
        } else {
            $badgeData['position_category'] = $rule->getData('position_category') ?: 'top-left';
            $badgeData['position_product'] = $rule->getData('position_product') ?: 'top-left';
            $badgeData['position_slider'] = $rule->getData('position_slider') ?: 'top-left';
        }

        $badgeStyle = $rule->getData('badge_style');
        if ($badgeStyle) {
            if (is_string($badgeStyle)) {
                $badgeStyle = json_decode($badgeStyle, true);
            }
            if (is_array($badgeStyle) && !empty($badgeStyle)) {
                $badgeData['badge_style'] = $badgeStyle;
            }
        }

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
