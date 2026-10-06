<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Ui\DataProvider\Rule;

use Magento\Framework\Api\Filter;
use Magento\Framework\Data\Collection;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\View\Element\UiComponent\DataProvider\FilterApplierInterface;

class FulltextFilter implements FilterApplierInterface
{
    public const SEARCH_FIELDS = ['name', 'badge_type', 'badge_text', 'product_ids', 'category_ids'];

    public function apply(Collection $collection, Filter $filter)
    {
        if (!$collection instanceof AbstractDb) {
            return;
        }

        $value = trim((string)$filter->getValue());
        if ($value === '') {
            return;
        }

        $like = '%' . addcslashes($value, '\\%_') . '%';
        $conditions = [];
        foreach (self::SEARCH_FIELDS as $field) {
            $conditions[] = ['like' => $like];
        }

        $collection->addFieldToFilter(self::SEARCH_FIELDS, $conditions);
    }
}
