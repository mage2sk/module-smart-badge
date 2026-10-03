<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Test\Unit\Controller\Adminhtml\Rule;

use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\DataObject;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Panth\SmartBadge\Controller\Adminhtml\Rule\CategoryTree;
use Panth\SmartBadge\Controller\Adminhtml\Rule\Delete;
use Panth\SmartBadge\Controller\Adminhtml\Rule\ProductsGrid;
use Panth\SmartBadge\Controller\Adminhtml\Rule\Validate;
use Panth\SmartBadge\Model\ResourceModel\Rule as RuleResource;
use Panth\SmartBadge\Model\RuleFactory;
use Panth\SmartBadge\Test\Unit\Fixture\BuildsBackendContext;
use Panth\SmartBadge\Test\Unit\Fixture\BuildsRules;
use PHPUnit\Framework\TestCase;

class SimpleActionsTest extends TestCase
{
    use BuildsBackendContext;
    use BuildsRules;

    private function storeManager(): StoreManagerInterface
    {
        $store = $this->createStub(Store::class);
        $store->method('getBaseUrl')->willReturn('https://shop.test/');
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);
        return $storeManager;
    }

    private function scopeConfig(string $suffix): ScopeConfigInterface
    {
        $config = $this->createStub(ScopeConfigInterface::class);
        $config->method('getValue')->willReturn($suffix);
        return $config;
    }

    public function testValidateRequiresName(): void
    {
        $this->request = $this->createStub(Http::class);
        $this->request->method('getPostValue')->willReturn(['name' => '']);
        (new Validate($this->backendContext(), $this->jsonFactory()))->execute();

        $this->assertTrue($this->jsonPayload['error']);
        $this->assertSame('Rule name is required.', (string)$this->jsonPayload['messages'][0]);
    }

    public function testValidatePassesWithName(): void
    {
        $this->request = $this->createStub(Http::class);
        $this->request->method('getPostValue')->willReturn(['name' => 'Rule']);
        (new Validate($this->backendContext(), $this->jsonFactory()))->execute();

        $this->assertSame(['error' => false], $this->jsonPayload);
    }

    public function testDeleteWithoutIdOnlyRedirects(): void
    {
        $resource = $this->createMock(RuleResource::class);
        $resource->expects($this->never())->method('delete');
        (new Delete($this->backendContext(), $this->createStub(RuleFactory::class), $resource))->execute();

        $this->assertSame('*/*/', $this->redirectPath);
        $this->assertSame([], $this->messages['success']);
    }

    public function testDeleteRemovesRule(): void
    {
        $this->request = $this->createStub(Http::class);
        $this->request->method('getParam')->willReturn('3');
        $factory = $this->createStub(RuleFactory::class);
        $factory->method('create')->willReturn($this->makeRule());
        $resource = $this->createMock(RuleResource::class);
        $resource->expects($this->once())->method('load')->with($this->anything(), '3');
        $resource->expects($this->once())->method('delete');

        (new Delete($this->backendContext(), $factory, $resource))->execute();

        $this->assertSame(['Badge rule deleted successfully.'], $this->messages['success']);
        $this->assertSame('*/*/', $this->redirectPath);
    }

    public function testDeleteFailureShowsError(): void
    {
        $this->request = $this->createStub(Http::class);
        $this->request->method('getParam')->willReturn('3');
        $factory = $this->createStub(RuleFactory::class);
        $factory->method('create')->willReturn($this->makeRule());
        $resource = $this->createStub(RuleResource::class);
        $resource->method('delete')->willThrowException(new \RuntimeException('constraint'));

        (new Delete($this->backendContext(), $factory, $resource))->execute();

        $this->assertSame(['constraint'], $this->messages['error']);
    }

    public function testProductsGridBuildsRowsWithUrls(): void
    {
        $collection = $this->createStub(ProductCollection::class);
        $collection->method('addAttributeToSelect')->willReturnSelf();
        $collection->method('addAttributeToFilter')->willReturnSelf();
        $collection->method('setPageSize')->willReturnSelf();
        $collection->method('setOrder')->willReturnSelf();
        $collection->method('getIterator')->willReturn(new \ArrayIterator([
            new DataObject(['id' => '4', 'name' => 'Tee', 'sku' => 'T1', 'type_id' => 'simple', 'price' => '9.5', 'url_key' => 'tee']),
            new DataObject(['id' => 5, 'name' => 'Box', 'sku' => 'B1', 'type_id' => 'bundle', 'price' => null]),
        ]));
        $factory = $this->createStub(ProductCollectionFactory::class);
        $factory->method('create')->willReturn($collection);

        (new ProductsGrid(
            $this->backendContext(),
            $factory,
            $this->jsonFactory(),
            $this->scopeConfig('.html'),
            $this->storeManager()
        ))->execute();

        $this->assertSame([
            ['id' => 4, 'name' => 'Tee', 'sku' => 'T1', 'type' => 'simple', 'price' => 9.5, 'url' => 'https://shop.test/tee.html'],
            ['id' => 5, 'name' => 'Box', 'sku' => 'B1', 'type' => 'bundle', 'price' => 0.0, 'url' => ''],
        ], $this->jsonPayload['products']);
    }

    public function testCategoryTreeNestsChildrenUnderParents(): void
    {
        $collection = $this->createStub(CategoryCollection::class);
        $collection->method('addAttributeToSelect')->willReturnSelf();
        $collection->method('addAttributeToFilter')->willReturnSelf();
        $collection->method('setOrder')->willReturnSelf();
        $collection->method('getIterator')->willReturn(new \ArrayIterator([
            new DataObject(['id' => 2, 'name' => 'Root', 'level' => 1, 'parent_id' => 1]),
            new DataObject(['id' => 3, 'name' => 'Men', 'level' => 2, 'parent_id' => 2, 'url_path' => 'men']),
            new DataObject(['id' => 4, 'name' => 'Shirts', 'level' => 3, 'parent_id' => 3, 'url_key' => 'shirts']),
        ]));
        $factory = $this->createStub(CategoryCollectionFactory::class);
        $factory->method('create')->willReturn($collection);

        (new CategoryTree(
            $this->backendContext(),
            $factory,
            $this->jsonFactory(),
            $this->scopeConfig(''),
            $this->storeManager()
        ))->execute();

        $tree = json_decode(json_encode($this->jsonPayload), true)['categories'];
        $this->assertCount(1, $tree);
        $this->assertSame('Root', $tree[0]['name']);
        $this->assertSame('', $tree[0]['url']);
        $this->assertSame('Men', $tree[0]['children'][0]['name']);
        $this->assertSame('https://shop.test/men', $tree[0]['children'][0]['url']);
        $this->assertSame('https://shop.test/shirts', $tree[0]['children'][0]['children'][0]['url']);
    }
}
