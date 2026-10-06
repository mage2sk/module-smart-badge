<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Test\Unit\Controller\Badge;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Panth\SmartBadge\Controller\Badge\Get;
use Panth\SmartBadge\Helper\BadgeHelper;
use PHPUnit\Framework\TestCase;

class GetTest extends TestCase
{
    private ?array $payload = null;
    private Http $request;
    private ProductRepositoryInterface $repository;
    private BadgeHelper $helper;

    protected function setUp(): void
    {
        $this->request = $this->createStub(Http::class);
        $this->repository = $this->createStub(ProductRepositoryInterface::class);
        $this->helper = $this->createStub(BadgeHelper::class);
        $this->helper->method('getProductBadges')->willReturn([['type' => 'sale']]);
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

        (new Get($factory, $this->request, $this->helper, $this->repository))->execute();
        return $this->payload;
    }

    public function testCsrfIsBypassedForStorefrontAjax(): void
    {
        $controller = new Get(
            $this->createStub(JsonFactory::class),
            $this->request,
            $this->helper,
            $this->repository
        );
        $this->assertTrue($controller->validateForCsrf($this->request));
        $this->assertNull($controller->createCsrfValidationException($this->request));
    }

    public function testMissingOrInvalidProductIdReturnsEmpty(): void
    {
        $this->request->method('getContent')->willReturn('');
        $this->request->method('getParam')->willReturn(['array']);
        $this->assertSame(['badges' => []], $this->runController());
    }

    public function testJsonBodyProductIdIsUsed(): void
    {
        $this->request->method('getContent')->willReturn('{"product_id":"12"}');
        $this->repository->method('getById')->willReturnCallback(
            static fn($id) => new DataObject(['id' => $id, 'status' => 1])
        );
        $this->assertSame(['badges' => [['type' => 'sale']]], $this->runController());
    }

    public function testQueryParamFallbackAndDisabledProduct(): void
    {
        $this->request->method('getContent')->willReturn('not json');
        $this->request->method('getParam')->willReturn('8');
        $this->repository->method('getById')->willReturn(new DataObject(['id' => 8, 'status' => 2]));
        $this->assertSame(['badges' => []], $this->runController());
    }

    public function testRepositoryExceptionReturnsEmpty(): void
    {
        $this->request->method('getContent')->willReturn('{"product_id":99}');
        $this->repository->method('getById')->willThrowException(new NoSuchEntityException());
        $this->assertSame(['badges' => []], $this->runController());
    }
}
