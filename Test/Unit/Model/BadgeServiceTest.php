<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Test\Unit\Model;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Panth\SmartBadge\Model\BadgeService;
use Panth\SmartBadge\Model\ConditionEvaluator;
use Panth\SmartBadge\Model\ResourceModel\Rule\Collection as RuleCollection;
use Panth\SmartBadge\Model\ResourceModel\Rule\CollectionFactory as RuleCollectionFactory;
use Panth\SmartBadge\Test\Unit\Fixture\BuildsRules;
use PHPUnit\Framework\TestCase;

class BadgeServiceTest extends TestCase
{
    use BuildsRules;

    private ConditionEvaluator $evaluator;
    private CategoryRepositoryInterface $categoryRepository;
    private int $storeId = 1;
    private int $groupId = 0;

    protected function setUp(): void
    {
        $this->evaluator = $this->createStub(ConditionEvaluator::class);
        $this->evaluator->method('isScheduleActive')->willReturn(true);
        $this->evaluator->method('evaluateConditions')->willReturn(true);
        $this->categoryRepository = $this->createStub(CategoryRepositoryInterface::class);
    }

    private function service(array $rules, ?RuleCollectionFactory $factory = null): BadgeService
    {
        if ($factory === null) {
            $collection = $this->createStub(RuleCollection::class);
            $collection->method('addFieldToFilter')->willReturnSelf();
            $collection->method('setOrder')->willReturnSelf();
            $collection->method('getItems')->willReturn($rules);
            $factory = $this->createStub(RuleCollectionFactory::class);
            $factory->method('create')->willReturn($collection);
        }

        $store = $this->createStub(Store::class);
        $store->method('getId')->willReturn($this->storeId);
        $store->method('getBaseUrl')->willReturn('https://shop.test/media/');
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        $httpContext = $this->createStub(HttpContext::class);
        $httpContext->method('getValue')->willReturnCallback(fn() => $this->groupId);

        return new BadgeService($factory, $this->evaluator, $this->categoryRepository, $storeManager, $httpContext);
    }

    private function product(int $id = 10, array $categoryIds = []): DataObject
    {
        return new DataObject(['id' => $id, 'category_ids' => $categoryIds]);
    }

    public function testProductWithoutIdGetsNoBadges(): void
    {
        $service = $this->service([$this->makeRule(['badge_type' => 'sale'])]);

        $this->assertSame([], $service->getRuleBadgesForProduct(null));
        $this->assertSame([], $service->getRuleBadgesForProduct(new DataObject([])));
        $this->assertNull($service->getRuleBadgeForProduct(null));
    }

    public function testRuleWithoutTargetsMatchesEveryProductWithDefaults(): void
    {
        $rule = $this->makeRule(['badge_type' => 'sale', 'priority' => '40']);
        $badge = $this->service([$rule])->getRuleBadgeForProduct($this->product());

        $this->assertSame('sale', $badge['type']);
        $this->assertSame('New', $badge['label']);
        $this->assertSame('badge-sale', $badge['class']);
        $this->assertSame(40, $badge['priority']);
        $this->assertSame('--badge-sale', $badge['cssVar']);
        $this->assertSame('all', $badge['display_on']);
        $this->assertFalse($badge['use_same_position']);
        $this->assertSame('top-left', $badge['position_category']);
        $this->assertArrayNotHasKey('image', $badge);
        $this->assertArrayNotHasKey('animation', $badge);
    }

    public function testProductIdTargeting(): void
    {
        $service = $this->service([$this->makeRule(['product_ids' => '3,4', 'badge_text' => 'Pick'])]);

        $this->assertCount(1, $service->getRuleBadgesForProduct($this->product(4)));
        $this->assertSame([], $service->getRuleBadgesForProduct($this->product(5)));
    }

    public function testCategoryTargetingMatchesDirectCategory(): void
    {
        $service = $this->service([$this->makeRule(['category_ids' => '8'])]);

        $this->assertCount(1, $service->getRuleBadgesForProduct($this->product(1, ['8'])));
        $this->assertSame([], $service->getRuleBadgesForProduct($this->product(1, [])));
    }

    public function testCategoryTargetingMatchesParentCategoryAndCachesLookups(): void
    {
        $category = $this->createStub(CategoryInterface::class);
        $category->method('getPath')->willReturn('1/2/8/15');
        $this->categoryRepository = $this->createMock(CategoryRepositoryInterface::class);
        $this->categoryRepository->expects($this->once())->method('get')->with(15)->willReturn($category);

        $service = $this->service([$this->makeRule(['category_ids' => '8'])]);

        $this->assertCount(1, $service->getRuleBadgesForProduct($this->product(1, [15])));
        $this->assertCount(1, $service->getRuleBadgesForProduct($this->product(2, [15])));
    }

    public function testRootCategoriesDoNotMatchAndMissingCategoryIsTolerated(): void
    {
        $this->categoryRepository->method('get')->willThrowException(new NoSuchEntityException());
        $service = $this->service([$this->makeRule(['category_ids' => '1,2'])]);

        $this->assertSame([], $service->getRuleBadgesForProduct($this->product(1, [99])));
    }

    public function testStoreAndCustomerGroupScope(): void
    {
        $rules = [
            $this->makeRule(['badge_text' => 'all-stores', 'store_ids' => '0']),
            $this->makeRule(['badge_text' => 'store-2', 'store_ids' => '2']),
            $this->makeRule(['badge_text' => 'group-1', 'customer_group_ids' => '1']),
        ];
        $this->groupId = 1;
        $labels = array_column($this->service($rules)->getRuleBadgesForProduct($this->product()), 'label');

        $this->assertSame(['all-stores', 'group-1'], $labels);
    }

    public function testScheduleAndConditionsFilterRules(): void
    {
        $this->evaluator = $this->createStub(ConditionEvaluator::class);
        $this->evaluator->method('isScheduleActive')->willReturnCallback(
            static fn($rule) => $rule->getData('badge_text') !== 'expired'
        );
        $this->evaluator->method('evaluateConditions')->willReturnCallback(
            static fn($product, $conditions) => $conditions !== 'fail'
        );
        $rules = [
            $this->makeRule(['badge_text' => 'expired']),
            $this->makeRule(['badge_text' => 'failing', 'smart_conditions' => 'fail']),
            $this->makeRule(['badge_text' => 'ok']),
        ];

        $labels = array_column($this->service($rules)->getRuleBadgesForProduct($this->product()), 'label');
        $this->assertSame(['ok'], $labels);
    }

    public function testActiveRulesAreLoadedOnce(): void
    {
        $collection = $this->createStub(RuleCollection::class);
        $collection->method('addFieldToFilter')->willReturnSelf();
        $collection->method('setOrder')->willReturnSelf();
        $collection->method('getItems')->willReturn([$this->makeRule()]);
        $factory = $this->createMock(RuleCollectionFactory::class);
        $factory->expects($this->once())->method('create')->willReturn($collection);

        $service = $this->service([], $factory);
        $service->getRuleBadgesForProduct($this->product(1));
        $this->assertCount(1, $service->getRuleBadgesForProduct($this->product(2)));
    }

    public function testUnsafeBadgeTypeFallsBackToCustom(): void
    {
        $badge = $this->service([$this->makeRule(['badge_type' => '<script>'])])
            ->getRuleBadgeForProduct($this->product());

        $this->assertSame('custom', $badge['type']);
        $this->assertSame('badge-custom', $badge['class']);
        $this->assertSame('--badge-new', $badge['cssVar']);
    }

    public function testIconHandling(): void
    {
        $rules = [
            $this->makeRule(['badge_text' => 'a', 'badge_icon' => ' fa-solid fa-star ']),
            $this->makeRule(['badge_text' => 'b', 'badge_icon' => 'fa" onclick="x']),
            $this->makeRule(['badge_text' => 'c', 'badge_icon' => 'GO']),
        ];
        $badges = $this->service($rules)->getRuleBadgesForProduct($this->product());

        $this->assertSame('fa-solid fa-star', $badges[0]['icon']);
        $this->assertNotSame('fa" onclick="x', $badges[1]['icon']);
        $this->assertNotSame('', $badges[1]['icon']);
        $this->assertSame('GO', $badges[2]['icon']);
    }

    public function testImageColorAndAnimation(): void
    {
        $rules = [
            $this->makeRule([
                'badge_text' => 'good',
                'badge_image' => 'promo.png',
                'badge_color' => '#AA00ff',
                'animation' => 'pulse',
            ]),
            $this->makeRule([
                'badge_text' => 'bad',
                'badge_type' => 'hot',
                'badge_image' => '../etc/env.php',
                'badge_color' => 'red',
                'animation' => 'none',
            ]),
            $this->makeRule(['badge_text' => 'bad-anim', 'animation' => 'x;y']),
        ];
        $badges = $this->service($rules)->getRuleBadgesForProduct($this->product());

        $this->assertSame('https://shop.test/media/smartbadge/promo.png', $badges[0]['image']);
        $this->assertSame('#AA00ff', $badges[0]['customColor']);
        $this->assertArrayNotHasKey('cssVar', $badges[0]);
        $this->assertSame('pulse', $badges[0]['animation']);

        $this->assertArrayNotHasKey('image', $badges[1]);
        $this->assertArrayNotHasKey('customColor', $badges[1]);
        $this->assertSame('--badge-hot', $badges[1]['cssVar']);
        $this->assertArrayNotHasKey('animation', $badges[1]);
        $this->assertArrayNotHasKey('animation', $badges[2]);
    }

    public function testPositionsAndDisplayOn(): void
    {
        $rules = [
            $this->makeRule([
                'use_same_position' => 1,
                'position_all' => 'bottom-right',
                'display_on' => 'product_page',
            ]),
            $this->makeRule([
                'position_category' => 'center-left',
                'position_product' => 'nowhere',
                'display_on' => 'Bad Value!',
            ]),
        ];
        $badges = $this->service($rules)->getRuleBadgesForProduct($this->product());

        $this->assertTrue($badges[0]['use_same_position']);
        $this->assertSame('bottom-right', $badges[0]['position']);
        $this->assertSame('product_page', $badges[0]['display_on']);
        $this->assertArrayNotHasKey('position_category', $badges[0]);

        $this->assertSame('center-left', $badges[1]['position_category']);
        $this->assertSame('top-left', $badges[1]['position_product']);
        $this->assertSame('top-left', $badges[1]['position_slider']);
        $this->assertSame('all', $badges[1]['display_on']);
    }

    public function testBadgeStyleIsSanitized(): void
    {
        $style = [
            'width' => ['value' => '40', 'unit' => 'rem'],
            'height' => ['value' => '-5', 'unit' => 'px'],
            'fontSize' => '12',
            'borderRadius' => '4',
            'opacity' => 'abc',
            'fontWeight' => '700',
            'borderStyle' => 'url(x)',
            'borderColor' => '#fff',
            'padding' => ['top' => '2', 'left' => 'x'],
            'boxShadow' => ['enabled' => true, 'x' => '1', 'blur' => 'big'],
        ];
        $badge = $this->service([$this->makeRule(['badge_style' => json_encode($style)])])
            ->getRuleBadgeForProduct($this->product());

        $this->assertSame([
            'width' => ['value' => '40', 'unit' => 'rem'],
            'fontSize' => ['value' => '12', 'unit' => 'px'],
            'borderRadius' => '4',
            'fontWeight' => '700',
            'borderColor' => '#fff',
            'padding' => ['top' => '2'],
            'boxShadow' => ['enabled' => true, 'x' => 1.0],
        ], $badge['badge_style']);
    }

    public function testBadgeStyleShadowPresetAndEmptyStyleOmitted(): void
    {
        $rules = [
            $this->makeRule(['badge_text' => 'preset', 'badge_style' => ['boxShadow' => 'glow']]),
            $this->makeRule(['badge_text' => 'empty', 'badge_style' => ['boxShadow' => 'evil', 'fontWeight' => '950']]),
        ];
        $badges = $this->service($rules)->getRuleBadgesForProduct($this->product());

        $this->assertSame(['boxShadow' => 'glow'], $badges[0]['badge_style']);
        $this->assertArrayNotHasKey('badge_style', $badges[1]);
    }

    public function testImageOnlyFlag(): void
    {
        $rules = [
            $this->makeRule(['badge_text' => 'yes', 'image_settings' => '{"imageOnly":true}']),
            $this->makeRule(['badge_text' => 'string-false', 'image_settings' => ['imageOnly' => 'false']]),
            $this->makeRule(['badge_text' => 'none']),
        ];
        $badges = $this->service($rules)->getRuleBadgesForProduct($this->product());

        $this->assertTrue($badges[0]['imageOnly']);
        $this->assertArrayNotHasKey('imageOnly', $badges[1]);
        $this->assertArrayNotHasKey('imageOnly', $badges[2]);
    }
}
