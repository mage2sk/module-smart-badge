<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Test\Unit\Ui\DataProvider\Rule;

use Magento\Framework\Api\Filter;
use Magento\Framework\Data\Collection;
use Magento\Framework\Data\Collection\AbstractDb;
use Panth\SmartBadge\Ui\DataProvider\Rule\FulltextFilter;
use PHPUnit\Framework\TestCase;

class FulltextFilterTest extends TestCase
{
    private function filter(?string $value): Filter
    {
        return new Filter(['field' => 'fulltext', 'value' => $value, 'condition_type' => 'fulltext']);
    }

    public function testKeywordIsMatchedWithLikeAcrossSearchFields(): void
    {
        $collection = $this->createMock(AbstractDb::class);
        $collection->expects($this->once())
            ->method('addFieldToFilter')
            ->with(
                ['name', 'badge_type', 'badge_text', 'product_ids', 'category_ids'],
                array_fill(0, 5, ['like' => '%Wholesale%'])
            );

        (new FulltextFilter())->apply($collection, $this->filter('  Wholesale '));
    }

    public function testLikeWildcardsInKeywordAreEscaped(): void
    {
        $captured = null;
        $collection = $this->createStub(AbstractDb::class);
        $collection->method('addFieldToFilter')->willReturnCallback(
            function ($fields, $conditions) use (&$captured, $collection) {
                $captured = $conditions[0]['like'];
                return $collection;
            }
        );

        (new FulltextFilter())->apply($collection, $this->filter('100%_off\\'));

        $this->assertSame('%100\\%\\_off\\\\%', $captured);
    }

    public function testEmptyKeywordAddsNoFilter(): void
    {
        $collection = $this->createMock(AbstractDb::class);
        $collection->expects($this->never())->method('addFieldToFilter');

        $applier = new FulltextFilter();
        $applier->apply($collection, $this->filter('   '));
        $applier->apply($collection, $this->filter(null));
    }

    public function testNonDatabaseCollectionIsIgnored(): void
    {
        $collection = $this->createMock(Collection::class);
        $collection->expects($this->never())->method('addFieldToFilter');

        (new FulltextFilter())->apply($collection, $this->filter('Wholesale'));
    }
}
