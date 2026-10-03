<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Test\Unit\Model\Config\Source;

use Magento\Customer\Model\ResourceModel\Group\Collection as GroupCollection;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory as GroupCollectionFactory;
use Magento\Framework\DataObject;
use Magento\Store\Model\System\Store as SystemStore;
use Panth\SmartBadge\Model\Config\Source\Animation;
use Panth\SmartBadge\Model\Config\Source\BadgeLayout;
use Panth\SmartBadge\Model\Config\Source\BadgeOptions;
use Panth\SmartBadge\Model\Config\Source\BadgeType;
use Panth\SmartBadge\Model\Config\Source\CombinationMode;
use Panth\SmartBadge\Model\Config\Source\CustomerGroups;
use Panth\SmartBadge\Model\Config\Source\DisplayOn;
use Panth\SmartBadge\Model\Config\Source\FontAwesomeIcons;
use Panth\SmartBadge\Model\Config\Source\Position;
use Panth\SmartBadge\Model\Config\Source\StoreViews;
use PHPUnit\Framework\TestCase;

/**
 * The option values offered in admin must survive the sanitising rules that
 * BadgeService applies on the storefront, otherwise a saved choice silently
 * falls back to a default.
 */
class SourceModelsTest extends TestCase
{
    private function values(array $options): array
    {
        return array_column($options, 'value');
    }

    public function testPositionsMatchStorefrontWhitelist(): void
    {
        $this->assertSame(
            ['top-left', 'top-right', 'bottom-left', 'bottom-right', 'top-center', 'bottom-center', 'center-left', 'center-right'],
            $this->values((new Position())->toOptionArray())
        );
    }

    public function testBadgeTypesAreValidBadgeTypeTokens(): void
    {
        $values = $this->values((new BadgeType())->toOptionArray());
        $this->assertContains('sale', $values);
        foreach ($values as $value) {
            $this->assertMatchesRegularExpression('/^[a-z0-9_-]{1,50}$/i', $value);
        }
    }

    public function testManualBadgeOptionsStartWithAutoDetect(): void
    {
        $options = (new BadgeOptions())->getAllOptions();
        $this->assertSame('', $options[0]['value']);
        $this->assertSame(
            ['new', 'sale', 'hot', 'limited', 'bestseller', 'trending', 'exclusive', 'featured'],
            array_slice($this->values($options), 1)
        );
    }

    public function testAnimationsPassStorefrontPattern(): void
    {
        $values = $this->values((new Animation())->toOptionArray());
        $this->assertSame('none', $values[0]);
        $this->assertSame(count($values), count(array_unique($values)));
        foreach (array_slice($values, 1) as $value) {
            $this->assertMatchesRegularExpression('/^[a-z][a-z0-9]{0,49}$/i', $value);
        }
    }

    public function testFontAwesomeIconsPassIconSanitiser(): void
    {
        $values = $this->values((new FontAwesomeIcons())->toOptionArray());
        $this->assertSame('', $values[0]);
        foreach (array_slice($values, 1) as $value) {
            $this->assertStringStartsWith('fa', $value);
            $this->assertMatchesRegularExpression('/^[a-z0-9 _-]+$/i', $value);
        }
    }

    public function testDisplayOnValuesPassStorefrontPattern(): void
    {
        $values = $this->values((new DisplayOn())->toOptionArray());
        $this->assertSame('all', $values[0]);
        foreach ($values as $value) {
            $this->assertMatchesRegularExpression('/^[a-z_]{1,50}$/', $value);
        }
    }

    public function testCombinationModesAndLayouts(): void
    {
        $this->assertSame(['priority', 'collect_all'], $this->values((new CombinationMode())->toOptionArray()));
        $this->assertSame(
            ['vertical', 'horizontal', 'grid', 'compact'],
            $this->values((new BadgeLayout())->toOptionArray())
        );
    }

    public function testCustomerGroupsAreMappedAndCached(): void
    {
        $collection = $this->createStub(GroupCollection::class);
        $collection->method('setOrder')->willReturnSelf();
        $collection->method('getIterator')->willReturn(new \ArrayIterator([
            new DataObject(['id' => 0, 'code' => 'NOT LOGGED IN']),
            new DataObject(['id' => 1, 'code' => 'General']),
        ]));
        $factory = $this->createMock(GroupCollectionFactory::class);
        $factory->expects($this->once())->method('create')->willReturn($collection);

        $source = new CustomerGroups($factory);
        $expected = [
            ['value' => '0', 'label' => 'NOT LOGGED IN'],
            ['value' => '1', 'label' => 'General'],
        ];
        $this->assertSame($expected, $source->toOptionArray());
        $this->assertSame($expected, $source->toOptionArray());
    }

    public function testStoreViewsIncludeAllStoreViewsOption(): void
    {
        $systemStore = $this->createMock(SystemStore::class);
        $systemStore->expects($this->once())->method('getStoreValuesForForm')->with(false, true)
            ->willReturn([['value' => 0, 'label' => 'All Store Views']]);

        $this->assertSame([['value' => 0, 'label' => 'All Store Views']], (new StoreViews($systemStore))->toOptionArray());
    }
}
