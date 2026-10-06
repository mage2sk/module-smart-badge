<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Test\Unit\Controller\Badge;

use Magento\Catalog\Api\Data\ProductSearchResultsInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\DataObject;
use Panth\SmartBadge\Controller\Badge\Batch;
use Panth\SmartBadge\Helper\BadgeHelper;
use PHPUnit\Framework\TestCase;

class BatchTest extends TestCase
{
    private $payload = null;
    private Http $request;
    private ProductRepositoryInterface $repository;
    private BadgeHelper $helper;
    private SearchCriteriaBuilder $criteriaBuilder;

    protected function setUp(): void
    {
        $this->request = $this->createStub(Http::class);
        $this->repository = $this->createStub(ProductRepositoryInterface::class);
        $this->helper = $this->createStub(BadgeHelper::class);
        $this->criteriaBuilder = $this->createStub(SearchCriteriaBuilder::class);
        $this->criteriaBuilder->method('addFilter')->willReturnSelf();
        $this->criteriaBuilder->method('create')->willReturn($this->createStub(SearchCriteria::class));
    }

    private function runController(): array
    {
        $json = $this->createStub(Json::class);
        $json->method('setData')->willReturnCallback(function ($data) use ($json) {
            $this->payload = $data;
            return $json;
        });
        $factory = $this->createStub(JsonFactory::class);
        $factory->method('create')->willReturn($json);

        (new Batch($factory, $this->request, $this->helper, $this->repository, $this->criteriaBuilder))->execute();
        return json_decode(json_encode($this->payload), true);
    }

    private function items(array $products): void
    {
        $results = $this->createStub(ProductSearchResultsInterface::class);
        $results->method('getItems')->willReturn($products);
        $this->repository->method('getList')->willReturn($results);
    }

    public function testCsrfIsBypassed(): void
    {
        $controller = new Batch(
            $this->createStub(JsonFactory::class),
            $this->request,
            $this->helper,
            $this->repository,
            $this->criteriaBuilder
        );
        $this->assertTrue($controller->validateForCsrf($this->request));
        $this->assertNull($controller->createCsrfValidationException($this->request));
    }

    public function testNoIdsReturnsEmptyObject(): void
    {
        $this->request->method('getContent')->willReturn('');
        $this->request->method('getParam')->willReturn(null);
        $this->runController();

        $this->assertInstanceOf(\stdClass::class, $this->payload['products']);
        $this->assertSame([], (array)$this->payload['products']);
    }

    public function testDisabledModuleReturnsEmptyListsPerRequestedId(): void
    {
        $this->request->method('getContent')->willReturn('');
        $this->request->method('getParam')->willReturn('3, 3,abc,-1,5');
        $this->helper->method('isEnabled')->willReturn(false);

        $this->assertSame(['products' => ['3' => [], '5' => []]], $this->runController());
    }

    public function testBadgesAreReturnedForEnabledProductsOnly(): void
    {
        $this->request->method('getContent')->willReturn('{"product_ids":[1,2,[3]]}');
        $this->helper->method('isEnabled')->willReturn(true);
        $this->helper->method('getProductBadges')->willReturnCallback(
            static fn($product) => [['type' => 'p' . $product->getId()]]
        );
        $this->items([
            new DataObject(['id' => 1, 'status' => 1]),
            new DataObject(['id' => 2, 'status' => 2]),
        ]);

        $this->assertSame(['products' => ['1' => [['type' => 'p1']], '2' => []]], $this->runController());
    }

    public function testRequestIsCappedAtMaxProducts(): void
    {
        $this->request->method('getContent')->willReturn(json_encode(['product_ids' => range(1, 100)]));
        $this->helper->method('isEnabled')->willReturn(false);

        $this->assertCount(Batch::MAX_PRODUCTS, $this->runController()['products']);
    }

    public function testRepositoryFailureStillReturnsRequestedKeys(): void
    {
        $this->request->method('getContent')->willReturn('{"product_ids":"7"}');
        $this->helper->method('isEnabled')->willReturn(true);
        $this->repository->method('getList')->willThrowException(new \RuntimeException('db'));

        $this->assertSame(['products' => ['7' => []]], $this->runController());
    }
}
