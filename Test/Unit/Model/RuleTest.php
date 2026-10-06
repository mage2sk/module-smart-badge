<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Test\Unit\Model;

use Panth\SmartBadge\Model\Badge;
use Panth\SmartBadge\Model\BadgeFactory;
use Panth\SmartBadge\Test\Unit\Fixture\BuildsRules;
use PHPUnit\Framework\TestCase;

class RuleTest extends TestCase
{
    use BuildsRules;

    public function testProductAndCategoryIdsAreParsedAndZeroesDropped(): void
    {
        $rule = $this->makeRule(['product_ids' => '5,abc, 7,0', 'category_ids' => '3,4']);

        $this->assertSame([0 => 5, 2 => 7], $rule->getProductIds());
        $this->assertSame([3, 4], $rule->getCategoryIds());
    }

    public function testEmptyIdListsReturnEmptyArrays(): void
    {
        $rule = $this->makeRule();

        $this->assertSame([], $rule->getProductIds());
        $this->assertSame([], $rule->getCategoryIds());
        $this->assertSame([], $rule->getStoreIds());
        $this->assertSame([], $rule->getCustomerGroupIds());
    }

    public function testStoreAndGroupIdsKeepZeroAndDropInvalidAndDuplicates(): void
    {
        $rule = $this->makeRule(['store_ids' => '0, 1,1,x,-2', 'customer_group_ids' => '2,3,2']);

        $this->assertSame([0, 1], $rule->getStoreIds());
        $this->assertSame([2, 3], $rule->getCustomerGroupIds());
    }

    public function testGetBadgeWithoutBadgeIdReturnsNull(): void
    {
        $factory = $this->createMock(BadgeFactory::class);
        $factory->expects($this->never())->method('create');

        $this->assertNull($this->makeRule([], $factory)->getBadge());
    }

    public function testGetBadgeLoadsAndCachesBadge(): void
    {
        $badge = $this->createMock(Badge::class);
        $badge->expects($this->once())->method('load')->with(9)->willReturnSelf();
        $badge->method('getId')->willReturn(9);
        $factory = $this->createMock(BadgeFactory::class);
        $factory->expects($this->once())->method('create')->willReturn($badge);

        $rule = $this->makeRule(['badge_id' => '9'], $factory);

        $this->assertSame($badge, $rule->getBadge());
        $this->assertSame($badge, $rule->getBadge());
    }

    public function testGetBadgeReturnsNullWhenBadgeIsMissing(): void
    {
        $badge = $this->createStub(Badge::class);
        $badge->method('load')->willReturnSelf();
        $badge->method('getId')->willReturn(null);
        $factory = $this->createStub(BadgeFactory::class);
        $factory->method('create')->willReturn($badge);

        $this->assertNull($this->makeRule(['badge_id' => 4], $factory)->getBadge());
    }
}
