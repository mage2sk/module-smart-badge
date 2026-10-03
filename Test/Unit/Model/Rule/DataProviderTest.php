<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Test\Unit\Model\Rule;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Panth\SmartBadge\Model\ResourceModel\Rule\Collection;
use Panth\SmartBadge\Model\ResourceModel\Rule\CollectionFactory;
use Panth\SmartBadge\Model\Rule\DataProvider;
use Panth\SmartBadge\Test\Unit\Fixture\BuildsRules;
use PHPUnit\Framework\TestCase;

class DataProviderTest extends TestCase
{
    use BuildsRules;

    private string $mediaDir;
    private $persisted = null;
    private bool $cleared = false;

    protected function setUp(): void
    {
        $this->mediaDir = sys_get_temp_dir() . '/smartbadge_dp_' . uniqid();
        mkdir($this->mediaDir . '/smartbadge', 0777, true);
        file_put_contents($this->mediaDir . '/smartbadge/real.png', str_repeat('x', 123));
    }

    protected function tearDown(): void
    {
        if (is_file($this->mediaDir . '/smartbadge/real.png')) {
            unlink($this->mediaDir . '/smartbadge/real.png');
        }
        foreach ([$this->mediaDir . '/smartbadge', $this->mediaDir] as $dir) {
            if (is_dir($dir)) {
                rmdir($dir);
            }
        }
    }

    private function provider(array $rules, $ruleIdParam = null, ?Collection $collection = null): DataProvider
    {
        if ($collection === null) {
            $collection = $this->createStub(Collection::class);
            $collection->method('getItems')->willReturn($rules);
            $collection->method('getNewEmptyItem')->willReturnCallback(fn() => $this->makeRule());
        }
        $factory = $this->createStub(CollectionFactory::class);
        $factory->method('create')->willReturn($collection);

        $persistor = $this->createStub(DataPersistorInterface::class);
        $persistor->method('get')->willReturnCallback(fn() => $this->persisted);
        $persistor->method('clear')->willReturnCallback(function () {
            $this->cleared = true;
        });

        $store = $this->createStub(Store::class);
        $store->method('getBaseUrl')->willReturn('https://shop.test/media/');
        $store->method('getBaseMediaDir')->willReturn($this->mediaDir);
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        $request = $this->createStub(RequestInterface::class);
        $request->method('getParam')->willReturn($ruleIdParam);

        return new DataProvider('form', 'rule_id', 'rule_id', $factory, $persistor, $storeManager, $request);
    }

    public function testRuleDataIsPreparedForTheForm(): void
    {
        $rule = $this->makeRule([
            'rule_id' => 3,
            'name' => 'R',
            'image_settings' => '{"imageOnly":true}',
            'smart_conditions' => '{"price":{"enabled":true}}',
            'badge_style' => 'not json',
            'store_ids' => '',
            'customer_group_ids' => '1,2',
            'badge_image' => 'real.png',
        ]);

        $data = $this->provider([$rule])->getData();
        $row = $data[3];

        $this->assertSame(['imageOnly' => true], $row['image_settings']);
        $this->assertSame(
            json_encode(['price' => ['enabled' => true]], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            $row['smart_conditions']
        );
        $this->assertSame('not json', $row['badge_style']);
        $this->assertSame(['0'], $row['store_ids']);
        $this->assertSame(['1', '2'], $row['customer_group_ids']);
        $this->assertSame([[
            'name' => 'real.png',
            'url' => 'https://shop.test/media/smartbadge/real.png',
            'size' => 123,
            'type' => 'image/png',
        ]], $row['badge_image']);
    }

    public function testInvalidImageSettingsBecomeNullAndMissingImageHasZeroSize(): void
    {
        $rule = $this->makeRule([
            'rule_id' => 4,
            'image_settings' => '[broken',
            'badge_image' => 'missing.SVG',
        ]);

        $row = $this->provider([$rule])->getData()[4];

        $this->assertNull($row['image_settings']);
        $this->assertSame(0, $row['badge_image'][0]['size']);
        $this->assertSame('image/svg+xml', $row['badge_image'][0]['type']);
    }

    public function testRuleIdParamFiltersCollectionAndResultIsCached(): void
    {
        $collection = $this->createMock(Collection::class);
        $collection->expects($this->once())->method('addFieldToFilter')->with('rule_id', '9');
        $collection->expects($this->once())->method('getItems')->willReturn([$this->makeRule(['rule_id' => 9])]);

        $provider = $this->provider([], '9', $collection);
        $first = $provider->getData();

        $this->assertSame($first, $provider->getData());
        $this->assertArrayHasKey(9, $first);
    }

    public function testPersistedDataIsRestoredAndCleared(): void
    {
        $this->persisted = ['name' => 'Unsaved'];

        $data = $this->provider([])->getData();

        $this->assertSame(['name' => 'Unsaved'], $data['']);
        $this->assertTrue($this->cleared);
    }

    public function testEmptyCollectionReturnsEmptyArray(): void
    {
        $this->assertSame([], $this->provider([])->getData());
    }
}
