<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Test\Unit\Model;

use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Review\Model\ResourceModel\Review\Collection as ReviewCollection;
use Magento\Review\Model\ResourceModel\Review\CollectionFactory as ReviewCollectionFactory;
use Magento\Review\Model\ReviewFactory;
use Magento\Sales\Model\ResourceModel\Report\Bestsellers\Collection as BestsellersCollection;
use Magento\Sales\Model\ResourceModel\Report\Bestsellers\CollectionFactory as BestsellersFactory;
use Magento\Wishlist\Model\ResourceModel\Item\Collection as WishlistCollection;
use Magento\Wishlist\Model\ResourceModel\Item\CollectionFactory as WishlistCollectionFactory;
use Panth\SmartBadge\Model\ConditionEvaluator;
use PHPUnit\Framework\TestCase;

class ConditionEvaluatorTest extends TestCase
{
    private StockRegistryInterface $stockRegistry;
    private ReviewCollectionFactory $reviewCollectionFactory;
    private BestsellersFactory $bestsellersFactory;
    private WishlistCollectionFactory $wishlistCollectionFactory;
    private ConditionEvaluator $evaluator;

    protected function setUp(): void
    {
        $this->stockRegistry = $this->createStub(StockRegistryInterface::class);
        $this->reviewCollectionFactory = $this->createStub(ReviewCollectionFactory::class);
        $this->bestsellersFactory = $this->createStub(BestsellersFactory::class);
        $this->wishlistCollectionFactory = $this->createStub(WishlistCollectionFactory::class);

        $timezone = $this->createStub(TimezoneInterface::class);
        $timezone->method('date')->willReturnCallback(
            static fn($date = null) => new \DateTime($date === null ? 'now' : (string)$date)
        );

        $this->evaluator = new ConditionEvaluator(
            $this->createStub(ReviewFactory::class),
            $this->reviewCollectionFactory,
            $this->bestsellersFactory,
            $this->wishlistCollectionFactory,
            $this->stockRegistry,
            $timezone
        );
    }

    private function product(array $data): DataObject
    {
        return new DataObject($data + ['id' => 7]);
    }

    private function stock(float $qty, bool $inStock = true): void
    {
        $item = $this->createStub(StockItemInterface::class);
        $item->method('getQty')->willReturn($qty);
        $item->method('getIsInStock')->willReturn($inStock);
        $this->stockRegistry->method('getStockItem')->willReturn($item);
    }

    public function testScheduleWithoutDatesIsActive(): void
    {
        $this->assertTrue($this->evaluator->isScheduleActive(new DataObject([])));
    }

    public function testScheduleInFutureIsInactive(): void
    {
        $rule = new DataObject(['schedule_from' => '+2 days']);
        $this->assertFalse($this->evaluator->isScheduleActive($rule));
    }

    public function testScheduleInPastIsInactive(): void
    {
        $rule = new DataObject(['schedule_to' => '-2 days']);
        $this->assertFalse($this->evaluator->isScheduleActive($rule));
    }

    public function testScheduleSpanningNowIsActive(): void
    {
        $rule = new DataObject(['schedule_from' => '-1 day', 'schedule_to' => '+1 day']);
        $this->assertTrue($this->evaluator->isScheduleActive($rule));
    }

    public function testEmptyOrInvalidConditionsPass(): void
    {
        $product = $this->product([]);
        $this->assertTrue($this->evaluator->evaluateConditions($product, null));
        $this->assertTrue($this->evaluator->evaluateConditions($product, ''));
        $this->assertTrue($this->evaluator->evaluateConditions($product, '{not json'));
        $this->assertTrue($this->evaluator->evaluateConditions($product, '"scalar"'));
    }

    public function testDisabledConditionsAreSkipped(): void
    {
        $product = $this->product(['final_price' => 500]);
        $conditions = ['price' => ['enabled' => false, 'max' => 10], 'stock' => ['min' => 1]];
        $this->assertTrue($this->evaluator->evaluateConditions($product, $conditions));
    }

    public function testUnknownConditionTypePasses(): void
    {
        $this->assertTrue($this->evaluator->evaluateConditions(
            $this->product([]),
            ['mystery' => ['enabled' => true]]
        ));
    }

    public function testPriceConditionRespectsMinAndMax(): void
    {
        $json = json_encode(['price' => ['enabled' => true, 'min' => 10, 'max' => 100]]);
        $this->assertTrue($this->evaluator->evaluateConditions($this->product(['final_price' => 50]), $json));
        $this->assertFalse($this->evaluator->evaluateConditions($this->product(['final_price' => 5]), $json));
        $this->assertFalse($this->evaluator->evaluateConditions($this->product(['final_price' => 150]), $json));
    }

    public function testAllEnabledConditionsMustPass(): void
    {
        $conditions = [
            'price' => ['enabled' => true, 'min' => 10],
            'attribute' => ['enabled' => true, 'code' => 'color', 'value' => 'red'],
        ];
        $this->assertTrue($this->evaluator->evaluateConditions(
            $this->product(['final_price' => 20, 'color' => 'red']),
            $conditions
        ));
        $this->assertFalse($this->evaluator->evaluateConditions(
            $this->product(['final_price' => 20, 'color' => 'blue']),
            $conditions
        ));
    }

    public function testStockQuantityOperators(): void
    {
        $this->stock(5);
        $product = $this->product([]);
        $this->assertTrue($this->evaluator->evaluateConditions($product, ['stock' => ['enabled' => 1, 'value' => 10]]));
        $this->assertFalse($this->evaluator->evaluateConditions(
            $product,
            ['stock' => ['enabled' => 1, 'operator' => 'greater_than', 'value' => 10]]
        ));
        $this->assertTrue($this->evaluator->evaluateConditions(
            $product,
            ['stock' => ['enabled' => 1, 'operator' => 'equals', 'value' => 5]]
        ));
        $this->assertTrue($this->evaluator->evaluateConditions(
            $product,
            ['stock' => ['enabled' => 1, 'operator' => 'weird', 'value' => 0]]
        ));
    }

    public function testStockConditionsFailWhenRegistryThrows(): void
    {
        $this->stockRegistry->method('getStockItem')->willThrowException(new \Exception('no stock'));
        $this->assertFalse($this->evaluator->evaluateConditions(
            $this->product([]),
            ['stock' => ['enabled' => 1, 'value' => 10]]
        ));
        $this->assertFalse($this->evaluator->evaluateConditions(
            $this->product([]),
            ['stockStatus' => ['enabled' => 1]]
        ));
    }

    public function testDiscountCondition(): void
    {
        $product = $this->product(['price' => 100, 'final_price' => 70]);
        $this->assertTrue($this->evaluator->evaluateConditions($product, ['discount' => ['enabled' => 1, 'value' => 20]]));
        $this->assertFalse($this->evaluator->evaluateConditions($product, ['discount' => ['enabled' => 1, 'value' => 30]]));
        $this->assertTrue($this->evaluator->evaluateConditions(
            $product,
            ['discount' => ['enabled' => 1, 'operator' => 'equals', 'value' => 30]]
        ));
        $this->assertTrue($this->evaluator->evaluateConditions(
            $product,
            ['discount' => ['enabled' => 1, 'operator' => 'less_than', 'value' => 50]]
        ));
    }

    public function testDiscountConditionFailsForZeroRegularPrice(): void
    {
        $this->assertFalse($this->evaluator->evaluateConditions(
            $this->product(['price' => 0, 'final_price' => 0]),
            ['discount' => ['enabled' => 1, 'value' => -1]]
        ));
    }

    public function testAgeCondition(): void
    {
        $product = $this->product(['created_at' => date('Y-m-d H:i:s', strtotime('-5 days'))]);
        $this->assertTrue($this->evaluator->evaluateConditions($product, ['age' => ['enabled' => 1, 'value' => 10]]));
        $this->assertFalse($this->evaluator->evaluateConditions(
            $product,
            ['age' => ['enabled' => 1, 'operator' => 'greater_than', 'value' => 10]]
        ));
    }

    public function testStockStatusCondition(): void
    {
        $this->stock(0, false);
        $product = $this->product([]);
        $this->assertFalse($this->evaluator->evaluateConditions($product, ['status' => ['enabled' => 1]]));
        $this->assertTrue($this->evaluator->evaluateConditions(
            $product,
            ['stockStatus' => ['enabled' => 1, 'value' => 'out_of_stock']]
        ));
        $this->assertTrue($this->evaluator->evaluateConditions(
            $product,
            ['stockStatus' => ['enabled' => 1, 'value' => 'any']]
        ));
    }

    private function reviews(array $percents): void
    {
        $reviews = [];
        foreach ($percents as $reviewVotes) {
            $votes = array_map(static fn($p) => new DataObject(['percent' => $p]), $reviewVotes);
            $reviews[] = new DataObject(['rating_votes' => $votes]);
        }
        $collection = $this->createStub(ReviewCollection::class);
        $collection->method('addEntityFilter')->willReturnSelf();
        $collection->method('addStatusFilter')->willReturnSelf();
        $collection->method('getSize')->willReturn(count($reviews));
        $collection->method('getIterator')->willReturn(new \ArrayIterator($reviews));
        $this->reviewCollectionFactory->method('create')->willReturn($collection);
    }

    public function testRatingConditionAveragesVotesOnFiveStarScale(): void
    {
        $this->reviews([[100, 80], [60]]);
        $product = $this->product([]);
        $this->assertTrue($this->evaluator->evaluateConditions($product, ['rating' => ['enabled' => 1, 'value' => 3.5]]));
        $this->assertTrue($this->evaluator->evaluateConditions(
            $product,
            ['rating' => ['enabled' => 1, 'operator' => 'equals', 'value' => 4]]
        ));
        $this->assertFalse($this->evaluator->evaluateConditions(
            $product,
            ['rating' => ['enabled' => 1, 'operator' => 'less_than', 'value' => 4]]
        ));
    }

    public function testRatingConditionFailsWithoutReviews(): void
    {
        $this->reviews([]);
        $this->assertFalse($this->evaluator->evaluateConditions(
            $this->product([]),
            ['rating' => ['enabled' => 1, 'operator' => 'less_than', 'value' => 5]]
        ));
    }

    public function testSalesCountSumsOrderedQuantity(): void
    {
        $collection = $this->createStub(BestsellersCollection::class);
        $collection->method('addFieldToFilter')->willReturnSelf();
        $collection->method('getIterator')->willReturn(new \ArrayIterator([
            new DataObject(['qty_ordered' => 4]),
            new DataObject(['qty_ordered' => 6]),
        ]));
        $this->bestsellersFactory->method('create')->willReturn($collection);

        $product = $this->product([]);
        $this->assertTrue($this->evaluator->evaluateConditions($product, ['salesCount' => ['enabled' => 1, 'value' => 9]]));
        $this->assertFalse($this->evaluator->evaluateConditions($product, ['salesCount' => ['enabled' => 1, 'value' => 10]]));
        $this->assertTrue($this->evaluator->evaluateConditions(
            $product,
            ['salesCount' => ['enabled' => 1, 'operator' => 'equals', 'value' => 10]]
        ));
    }

    public function testSalesCountFailsOnException(): void
    {
        $this->bestsellersFactory->method('create')->willThrowException(new \RuntimeException('db'));
        $this->assertFalse($this->evaluator->evaluateConditions(
            $this->product([]),
            ['salesCount' => ['enabled' => 1, 'operator' => 'less_than', 'value' => 100]]
        ));
    }

    public function testWishlistCountCondition(): void
    {
        $collection = $this->createStub(WishlistCollection::class);
        $collection->method('addFieldToFilter')->willReturnSelf();
        $collection->method('getSize')->willReturn(3);
        $this->wishlistCollectionFactory->method('create')->willReturn($collection);

        $product = $this->product([]);
        $this->assertTrue($this->evaluator->evaluateConditions($product, ['wishlistCount' => ['enabled' => 1, 'value' => 2]]));
        $this->assertTrue($this->evaluator->evaluateConditions(
            $product,
            ['wishlistCount' => ['enabled' => 1, 'operator' => 'less_than', 'value' => 4]]
        ));
        $this->assertFalse($this->evaluator->evaluateConditions(
            $product,
            ['wishlistCount' => ['enabled' => 1, 'operator' => 'equals', 'value' => 4]]
        ));
    }

    public function testAttributeConditionOperators(): void
    {
        $product = $this->product(['material' => 'Organic Cotton']);
        $this->assertTrue($this->evaluator->evaluateConditions(
            $product,
            ['attribute' => ['enabled' => 1, 'code' => 'material', 'operator' => 'contains', 'value' => 'cotton']]
        ));
        $this->assertTrue($this->evaluator->evaluateConditions(
            $product,
            ['attribute' => ['enabled' => 1, 'code' => 'material', 'operator' => 'not_equals', 'value' => 'Wool']]
        ));
        $this->assertFalse($this->evaluator->evaluateConditions(
            $product,
            ['attribute' => ['enabled' => 1, 'code' => 'missing', 'value' => 'x']]
        ));
        $this->assertTrue($this->evaluator->evaluateConditions(
            $product,
            ['attribute' => ['enabled' => 1, 'code' => '', 'value' => 'x']]
        ));
    }

    public function testDateRangeCondition(): void
    {
        $product = $this->product([]);
        $this->assertTrue($this->evaluator->evaluateConditions(
            $product,
            ['dateRange' => ['enabled' => 1, 'from' => '-1 day', 'to' => '+1 day']]
        ));
        $this->assertFalse($this->evaluator->evaluateConditions(
            $product,
            ['dateRange' => ['enabled' => 1, 'from' => '+1 day']]
        ));
        $this->assertFalse($this->evaluator->evaluateConditions(
            $product,
            ['dateRange' => ['enabled' => 1, 'to' => '-1 day']]
        ));
    }
}
