<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Review\Model\ReviewFactory;
use Magento\Review\Model\ResourceModel\Review\CollectionFactory as ReviewCollectionFactory;
use Magento\Sales\Model\ResourceModel\Report\Bestsellers\CollectionFactory as BestsellersFactory;
use Magento\Wishlist\Model\ResourceModel\Item\CollectionFactory as WishlistCollectionFactory;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class ConditionEvaluator
{
    private $reviewFactory;
    private $reviewCollectionFactory;
    private $bestsellersFactory;
    private $wishlistCollectionFactory;
    private $stockRegistry;
    private $timezone;

    public function __construct(
        ReviewFactory $reviewFactory,
        ReviewCollectionFactory $reviewCollectionFactory,
        BestsellersFactory $bestsellersFactory,
        WishlistCollectionFactory $wishlistCollectionFactory,
        StockRegistryInterface $stockRegistry,
        TimezoneInterface $timezone
    ) {
        $this->reviewFactory = $reviewFactory;
        $this->reviewCollectionFactory = $reviewCollectionFactory;
        $this->bestsellersFactory = $bestsellersFactory;
        $this->wishlistCollectionFactory = $wishlistCollectionFactory;
        $this->stockRegistry = $stockRegistry;
        $this->timezone = $timezone;
    }

    /**
     * Check if rule is within scheduled date range
     */
    public function isScheduleActive($rule): bool
    {
        $scheduleFrom = $rule->getData('schedule_from');
        $scheduleTo = $rule->getData('schedule_to');

        // If no schedule, always active
        if (!$scheduleFrom && !$scheduleTo) {
            return true;
        }

        $now = $this->timezone->date();

        // Check start date
        if ($scheduleFrom) {
            $startDate = $this->timezone->date($scheduleFrom);
            if ($now < $startDate) {
                return false;
            }
        }

        // Check end date
        if ($scheduleTo) {
            $endDate = $this->timezone->date($scheduleTo);
            if ($now > $endDate) {
                return false;
            }
        }

        return true;
    }

    /**
     * Evaluate all smart conditions for a product
     */
    public function evaluateConditions($product, $smartConditions): bool
    {
        if (!$smartConditions) {
            return true; // No conditions = always match
        }

        $conditions = is_string($smartConditions) ? json_decode($smartConditions, true) : $smartConditions;

        if (!$conditions || !is_array($conditions)) {
            return true;
        }

        // Evaluate each enabled condition
        foreach ($conditions as $conditionType => $condition) {
            if (!isset($condition['enabled']) || !$condition['enabled']) {
                continue; // Skip disabled conditions
            }

            $result = $this->evaluateCondition($product, $conditionType, $condition);
            if (!$result) {
                return false; // All conditions must pass
            }
        }

        return true;
    }

    /**
     * Evaluate a single condition
     */
    private function evaluateCondition($product, string $type, array $condition): bool
    {
        switch ($type) {
            case 'price':
                return $this->evaluatePriceCondition($product, $condition);

            case 'stock':
                return $this->evaluateStockCondition($product, $condition);

            case 'discount':
                return $this->evaluateDiscountCondition($product, $condition);

            case 'age':
                return $this->evaluateAgeCondition($product, $condition);

            case 'status':
            case 'stockStatus':
                return $this->evaluateStockStatusCondition($product, $condition);

            case 'rating':
                return $this->evaluateRatingCondition($product, $condition);

            case 'salesCount':
                return $this->evaluateSalesCountCondition($product, $condition);

            case 'wishlistCount':
                return $this->evaluateWishlistCountCondition($product, $condition);

            case 'attribute':
                return $this->evaluateAttributeCondition($product, $condition);

            case 'dateRange':
                return $this->evaluateDateRangeCondition($condition);

            default:
                return true;
        }
    }

    /**
     * Evaluate price range condition
     */
    private function evaluatePriceCondition($product, array $condition): bool
    {
        $price = (float)$product->getFinalPrice();

        if (!empty($condition['min']) && $price < (float)$condition['min']) {
            return false;
        }

        if (!empty($condition['max']) && $price > (float)$condition['max']) {
            return false;
        }

        return true;
    }

    /**
     * Evaluate stock level condition
     */
    private function evaluateStockCondition($product, array $condition): bool
    {
        try {
            $stockItem = $this->stockRegistry->getStockItem($product->getId());
            $qty = $stockItem->getQty();
            $operator = $condition['operator'] ?? 'less_than';
            $value = (float)($condition['value'] ?? 0);

            switch ($operator) {
                case 'less_than':
                    return $qty < $value;
                case 'greater_than':
                    return $qty > $value;
                case 'equals':
                    return $qty == $value;
                default:
                    return true;
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Evaluate discount percentage condition
     */
    private function evaluateDiscountCondition($product, array $condition): bool
    {
        $regularPrice = (float)$product->getPrice();
        $finalPrice = (float)$product->getFinalPrice();

        if ($regularPrice <= 0) {
            return false;
        }

        $discountPercent = (($regularPrice - $finalPrice) / $regularPrice) * 100;
        $operator = $condition['operator'] ?? 'greater_than';
        $value = (float)($condition['value'] ?? 0);

        switch ($operator) {
            case 'greater_than':
                return $discountPercent > $value;
            case 'less_than':
                return $discountPercent < $value;
            case 'equals':
                return abs($discountPercent - $value) < 0.01;
            default:
                return true;
        }
    }

    /**
     * Evaluate product age condition
     */
    private function evaluateAgeCondition($product, array $condition): bool
    {
        $createdAt = strtotime($product->getCreatedAt());
        $daysSinceCreation = (time() - $createdAt) / (60 * 60 * 24);
        $operator = $condition['operator'] ?? 'less_than';
        $value = (float)($condition['value'] ?? 0);

        switch ($operator) {
            case 'less_than':
                return $daysSinceCreation < $value;
            case 'greater_than':
                return $daysSinceCreation > $value;
            default:
                return true;
        }
    }

    /**
     * Evaluate stock status condition (in_stock/out_of_stock)
     */
    private function evaluateStockStatusCondition($product, array $condition): bool
    {
        try {
            $stockItem = $this->stockRegistry->getStockItem($product->getId());
            $isInStock = $stockItem->getIsInStock();
            $value = $condition['value'] ?? 'in_stock';

            if ($value === 'in_stock') {
                return (bool)$isInStock;
            } elseif ($value === 'out_of_stock') {
                return !(bool)$isInStock;
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Evaluate customer rating condition
     */
    private function evaluateRatingCondition($product, array $condition): bool
    {
        try {
            $reviewCollection = $this->reviewCollectionFactory->create()
                ->addEntityFilter('product', $product->getId())
                ->addStatusFilter(\Magento\Review\Model\Review::STATUS_APPROVED);

            if ($reviewCollection->getSize() == 0) {
                return false;
            }

            $ratingSum = 0;
            $count = 0;

            foreach ($reviewCollection as $review) {
                foreach ($review->getRatingVotes() as $vote) {
                    $ratingSum += $vote->getPercent() / 20; // Convert 0-100 to 0-5
                    $count++;
                }
            }

            $averageRating = $count > 0 ? $ratingSum / $count : 0;
            $operator = $condition['operator'] ?? 'greater_than';
            $value = (float)($condition['value'] ?? 0);

            switch ($operator) {
                case 'greater_than':
                    return $averageRating > $value;
                case 'less_than':
                    return $averageRating < $value;
                case 'equals':
                    return abs($averageRating - $value) < 0.1;
                default:
                    return true;
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Evaluate sales count condition
     */
    private function evaluateSalesCountCondition($product, array $condition): bool
    {
        try {
            $bestsellers = $this->bestsellersFactory->create()
                ->addFieldToFilter('product_id', $product->getId());

            $totalQty = 0;
            foreach ($bestsellers as $item) {
                $totalQty += $item->getQtyOrdered();
            }

            $operator = $condition['operator'] ?? 'greater_than';
            $value = (float)($condition['value'] ?? 0);

            switch ($operator) {
                case 'greater_than':
                    return $totalQty > $value;
                case 'less_than':
                    return $totalQty < $value;
                case 'equals':
                    return $totalQty == $value;
                default:
                    return true;
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Evaluate wishlist count condition
     */
    private function evaluateWishlistCountCondition($product, array $condition): bool
    {
        try {
            $wishlistCollection = $this->wishlistCollectionFactory->create()
                ->addFieldToFilter('product_id', $product->getId());

            $count = $wishlistCollection->getSize();
            $operator = $condition['operator'] ?? 'greater_than';
            $value = (float)($condition['value'] ?? 0);

            switch ($operator) {
                case 'greater_than':
                    return $count > $value;
                case 'less_than':
                    return $count < $value;
                case 'equals':
                    return $count == $value;
                default:
                    return true;
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Evaluate product attribute condition
     */
    private function evaluateAttributeCondition($product, array $condition): bool
    {
        $attributeCode = $condition['code'] ?? '';
        if (!$attributeCode) {
            return true;
        }

        $attributeValue = $product->getData($attributeCode);
        if ($attributeValue === null) {
            return false;
        }

        $operator = $condition['operator'] ?? 'equals';
        $value = $condition['value'] ?? '';

        switch ($operator) {
            case 'equals':
                return $attributeValue == $value;
            case 'not_equals':
                return $attributeValue != $value;
            case 'contains':
                return stripos((string)$attributeValue, $value) !== false;
            default:
                return true;
        }
    }

    /**
     * Evaluate date range condition
     */
    private function evaluateDateRangeCondition(array $condition): bool
    {
        $now = $this->timezone->date();

        if (!empty($condition['from'])) {
            $fromDate = $this->timezone->date($condition['from']);
            if ($now < $fromDate) {
                return false;
            }
        }

        if (!empty($condition['to'])) {
            $toDate = $this->timezone->date($condition['to']);
            if ($now > $toDate) {
                return false;
            }
        }

        return true;
    }
}
