<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Test\Unit\Helper;

use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Catalog\Pricing\Price\FinalPriceInterface;
use Magento\ConfigurableProduct\Pricing\Price\ConfigurableRegularPriceInterface;
use Magento\Framework\Pricing\Amount\AmountInterface;
use Magento\Framework\Pricing\Price\PriceInterface;
use Magento\Framework\Pricing\PriceInfoInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\DataObject;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Panth\SmartBadge\Helper\BadgeHelper;
use Panth\SmartBadge\Model\BadgeService;
use PHPUnit\Framework\TestCase;

class BadgeHelperTest extends TestCase
{
    private array $config = [];
    private array $ruleBadges = [];
    private bool $inDateInterval = true;
    private float $qty = 100;
    private bool $stockThrows = false;
    private bool $inStock = true;
    private array $intervalCalls = [];

    private function helper(): BadgeHelper
    {
        $scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturnCallback(fn($path) => $this->config[$path] ?? null);
        $scopeConfig->method('isSetFlag')->willReturnCallback(fn($path) => (bool)($this->config[$path] ?? false));
        $context = $this->createStub(Context::class);
        $context->method('getScopeConfig')->willReturn($scopeConfig);

        $timezone = $this->createStub(TimezoneInterface::class);
        $timezone->method('isScopeDateInInterval')->willReturnCallback(function ($scope, $from, $to) {
            $this->intervalCalls[] = [$from, $to];
            return $this->inDateInterval;
        });

        $stockItem = $this->createStub(StockItemInterface::class);
        $stockItem->method('getQty')->willReturnCallback(fn() => $this->qty);
        $stockItem->method('getIsInStock')->willReturnCallback(fn() => $this->inStock);
        $stockRegistry = $this->createStub(StockRegistryInterface::class);
        $stockRegistry->method('getStockItem')->willReturnCallback(function () use ($stockItem) {
            if ($this->stockThrows) {
                throw new \RuntimeException('no stock');
            }
            return $stockItem;
        });

        $badgeService = $this->createStub(BadgeService::class);
        $badgeService->method('getRuleBadgesForProduct')->willReturnCallback(fn() => $this->ruleBadges);

        return new BadgeHelper($context, $timezone, $stockRegistry, $badgeService);
    }

    private function product(array $data = []): DataObject
    {
        return new DataObject($data + [
            'id' => 1,
            'created_at' => date('Y-m-d H:i:s', strtotime('-400 days')),
            'price' => 100,
        ]);
    }

    private function enable(array $extra = []): void
    {
        $this->config = $extra + ['smart_badge/general/enabled' => 1];
    }

    public function testDisabledModuleReturnsNoBadges(): void
    {
        $this->ruleBadges = [['type' => 'sale']];
        $helper = $this->helper();

        $this->assertFalse($helper->isEnabled());
        $this->assertSame([], $helper->getProductBadges($this->product(['product_badge' => 'new'])));
    }

    public function testLayoutAndSpacingDefaults(): void
    {
        $helper = $this->helper();
        $this->assertSame('vertical', $helper->getBadgeLayout());
        $this->assertSame('gap-2', $helper->getBadgeSpacing());

        $this->config = [
            'smart_badge/display/badge_layout' => 'grid',
            'smart_badge/display/badge_spacing' => 'gap-4',
        ];
        $this->assertSame('grid', $helper->getBadgeLayout());
        $this->assertSame('gap-4', $helper->getBadgeSpacing());
    }

    public function testPriorityModeManualBadgeWinsWithCustomTextAndColor(): void
    {
        $this->enable(['smart_badge/display/use_custom_styling' => 1]);
        $this->ruleBadges = [['type' => 'rule']];
        $badges = $this->helper()->getProductBadges($this->product([
            'product_badge' => 'hot',
            'badge_custom_text' => 'Very hot',
            'badge_custom_color' => '#123abc',
        ]));

        $this->assertCount(1, $badges);
        $this->assertSame('hot', $badges[0]['type']);
        $this->assertSame('Very hot', $badges[0]['label']);
        $this->assertSame('badge-hot', $badges[0]['class']);
        $this->assertSame('#123abc', $badges[0]['customColor']);
        $this->assertArrayNotHasKey('cssVar', $badges[0]);
    }

    public function testInvalidCustomColorKeepsCssVar(): void
    {
        $this->enable();
        $badges = $this->helper()->getProductBadges($this->product([
            'product_badge' => 'limited',
            'badge_custom_color' => 'red;background:url(x)',
        ]));

        $this->assertSame('Limited', $badges[0]['label']);
        $this->assertSame('--badge-limited', $badges[0]['cssVar']);
        $this->assertArrayNotHasKey('customColor', $badges[0]);
    }

    public function testUnknownManualBadgeFallsThroughToRules(): void
    {
        $this->enable();
        $this->ruleBadges = [['type' => 'first'], ['type' => 'second']];
        $badges = $this->helper()->getProductBadges($this->product(['product_badge' => 'bogus']));

        $this->assertSame(['first'], array_column($badges, 'type'));
        $this->assertSame('custom', $badges[0]['tone']);
    }

    public function testPriorityModeAutoBadges(): void
    {
        $this->enable(['smart_badge/general/max_badges' => 3]);
        $this->qty = 4;
        $badges = $this->helper()->getProductBadges($this->product([
            'special_price' => 75,
            'created_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
        ]));

        $this->assertSame(['sale', 'new', 'stock'], array_column($badges, 'type'));
        $this->assertSame('-25%', $badges[0]['label']);
        $this->assertSame('Only 4 left', $badges[2]['label']);
    }

    public function testAutoBadgesRespectMaxBadges(): void
    {
        $this->enable(['smart_badge/general/max_badges' => 1]);
        $this->qty = 4;
        $badges = $this->helper()->getProductBadges($this->product([
            'special_price' => 75,
            'created_at' => date('Y-m-d H:i:s'),
        ]));

        $this->assertSame(['sale'], array_column($badges, 'type'));
    }

    public function testOutOfRangeMaxBadgesFallsBackToOne(): void
    {
        $this->enable(['smart_badge/general/max_badges' => 50, 'smart_badge/general/badge_combination_mode' => 'all']);
        $this->ruleBadges = [['type' => 'r1'], ['type' => 'r2'], ['type' => 'r3']];
        $this->config['smart_badge/general/show_multiple_rule_badges'] = 1;
        $this->config['smart_badge/general/auto_badges_with_manual'] = 1;
        $this->qty = 2;

        $badges = $this->helper()->getProductBadges($this->product(['product_badge' => 'new']));
        $this->assertCount(1, $badges);
        $this->assertSame('new', $badges[0]['type']);
    }

    public function testSaleBadgeRequiresActiveSpecialPriceWindow(): void
    {
        $this->enable();
        $this->inDateInterval = false;
        $this->assertSame([], $this->helper()->getProductBadges($this->product(['special_price' => 50])));

        $this->inDateInterval = true;
        $this->assertSame([], $this->helper()->getProductBadges($this->product(['special_price' => 150])));
    }

    public function testLowStockBadgeBoundaries(): void
    {
        $this->enable();
        $this->qty = 0;
        $this->assertSame([], $this->helper()->getProductBadges($this->product()));
        $this->qty = 11;
        $this->assertSame([], $this->helper()->getProductBadges($this->product()));
        $this->qty = 10;
        $this->assertSame('Only 10 left', $this->helper()->getProductBadges($this->product())[0]['label']);
    }

    public function testStockFailureProducesNoStockBadge(): void
    {
        $this->enable();
        $this->stockThrows = true;
        $this->assertSame([], $this->helper()->getProductBadges($this->product()));
    }

    public function testCollectAllModeOrdersManualThenRuleWithoutAutoBadges(): void
    {
        $this->enable([
            'smart_badge/general/badge_combination_mode' => 'collect_all',
            'smart_badge/general/max_badges' => 3,
        ]);
        $this->ruleBadges = [['type' => 'r1'], ['type' => 'r2']];
        $this->qty = 3;

        $badges = $this->helper()->getProductBadges($this->product(['product_badge' => 'sale']));

        $this->assertSame(['sale', 'r1'], array_column($badges, 'type'));
        $this->assertSame([100, 50], array_column($badges, 'priority'));
    }

    public function testCollectAllModeWithMultipleRulesAndAutoBadges(): void
    {
        $this->enable([
            'smart_badge/general/badge_combination_mode' => 'collect_all',
            'smart_badge/general/show_multiple_rule_badges' => 1,
            'smart_badge/general/auto_badges_with_manual' => 1,
            'smart_badge/general/max_badges' => 5,
        ]);
        $this->ruleBadges = [['type' => 'r1'], ['type' => 'r2']];
        $this->qty = 3;

        $badges = $this->helper()->getProductBadges($this->product(['product_badge' => 'featured']));

        $this->assertSame(['stock', 'featured', 'r1', 'r2'], array_column($badges, 'type'));
        $this->assertSame([10, 100, 50, 50], array_column($badges, 'priority'));
    }

    public function testCollectAllModeUsesAutoBadgesWhenNothingElseMatches(): void
    {
        $this->enable(['smart_badge/general/badge_combination_mode' => 'collect_all']);
        $this->qty = 1;

        $badges = $this->helper()->getProductBadges($this->product());

        $this->assertSame(['stock'], array_column($badges, 'type'));
        $this->assertSame(10, $badges[0]['priority']);
    }

    private function amount(float $value): AmountInterface
    {
        $amount = $this->createStub(AmountInterface::class);
        $amount->method('getValue')->willReturn($value);
        return $amount;
    }

    private function withPriceInfo(PriceInfoInterface $priceInfo, array $data): DataObject
    {
        return new class($data + ['id' => 1, 'price' => 100], $priceInfo) extends DataObject {
            private $priceInfo;

            public function __construct(array $data, $priceInfo)
            {
                parent::__construct($data);
                $this->priceInfo = $priceInfo;
            }

            public function getPriceInfo()
            {
                return $this->priceInfo;
            }
        };
    }

    private function pricedProduct(object $regular, object $final, array $data = []): DataObject
    {
        $priceInfo = $this->createStub(PriceInfoInterface::class);
        $priceInfo->method('getPrice')->willReturnCallback(
            fn($code) => $code === 'regular_price' ? $regular : $final
        );
        return $this->withPriceInfo($priceInfo, $data);
    }

    private function simplePrices(float $regular, float $final, array $data = []): DataObject
    {
        $regularPrice = $this->createStub(PriceInterface::class);
        $regularPrice->method('getAmount')->willReturn($this->amount($regular));
        $finalPrice = $this->createStub(FinalPriceInterface::class);
        $finalPrice->method('getMinimalPrice')->willReturn($this->amount($final));
        return $this->pricedProduct($regularPrice, $finalPrice, $data);
    }

    public function testCatalogRuleFinalPriceProducesSaleBadgeWithoutSpecialPrice(): void
    {
        $this->enable();
        $badges = $this->helper()->getProductBadges($this->simplePrices(48.0, 38.4));

        $this->assertSame(['sale'], array_column($badges, 'type'));
        $this->assertSame('-20%', $badges[0]['label']);
    }

    public function testConfigurableUsesMinimumRegularAmount(): void
    {
        $this->enable();
        $regularPrice = $this->createStub(ConfigurableRegularPriceInterface::class);
        $regularPrice->method('getMinRegularAmount')->willReturn($this->amount(80.0));
        $finalPrice = $this->createStub(FinalPriceInterface::class);
        $finalPrice->method('getMinimalPrice')->willReturn($this->amount(60.0));

        $badges = $this->helper()->getProductBadges($this->pricedProduct($regularPrice, $finalPrice));

        $this->assertSame('-25%', $badges[0]['label']);
    }

    public function testDiscountRoundingToZeroShowsNoSaleBadge(): void
    {
        $this->enable();
        $this->assertSame([], $this->helper()->getProductBadges($this->simplePrices(100.0, 99.6)));
        $this->assertSame([], $this->helper()->getProductBadges($this->product(['special_price' => 99.7])));
        $this->assertSame([], $this->helper()->getProductBadges($this->simplePrices(0.0, 0.0)));
    }

    public function testFinalPriceEqualToRegularPriceShowsNoSaleBadge(): void
    {
        $this->enable();
        $this->assertSame([], $this->helper()->getProductBadges($this->simplePrices(50.0, 50.0)));
    }

    public function testPriceInfoFailureFallsBackToSpecialPrice(): void
    {
        $this->enable();
        $priceInfo = $this->createStub(PriceInfoInterface::class);
        $priceInfo->method('getPrice')->willThrowException(new \RuntimeException('no price'));

        $badges = $this->helper()->getProductBadges($this->withPriceInfo($priceInfo, ['special_price' => 60]));

        $this->assertSame('-40%', $badges[0]['label']);
    }

    public function testNewFromToDatesDecideNewBadge(): void
    {
        $this->enable();
        $this->inDateInterval = true;
        $badges = $this->helper()->getProductBadges($this->product(['news_from_date' => '2026-01-01 00:00:00']));
        $this->assertSame(['new'], array_column($badges, 'type'));
        $this->assertSame([['2026-01-01 00:00:00', null]], $this->intervalCalls);

        $this->inDateInterval = false;
        $recent = $this->product([
            'news_to_date' => '2026-02-01 00:00:00',
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
        ]);
        $this->assertSame([], $this->helper()->getProductBadges($recent));
    }

    public function testNewBadgeFallsBackToCreationDate(): void
    {
        $this->enable();
        $this->assertSame([], $this->helper()->getProductBadges($this->product(['created_at' => ''])));
        $badges = $this->helper()->getProductBadges(
            $this->product(['created_at' => date('Y-m-d H:i:s', strtotime('-29 days'))])
        );
        $this->assertSame(['new'], array_column($badges, 'type'));
        $this->assertSame([], $this->intervalCalls);
    }

    public function testOutOfStockProductGetsNoLowStockBadge(): void
    {
        $this->enable();
        $this->qty = 3;
        $this->inStock = false;
        $this->assertSame([], $this->helper()->getProductBadges($this->product()));
    }

    public function testDefaultShowsOneBadgeAndSaleWinsOverRulesAndManualBadge(): void
    {
        $this->enable();
        $this->qty = 2;
        $this->ruleBadges = [['type' => 'bestseller', 'label' => 'Under $30', 'priority' => 99]];

        $badges = $this->helper()->getProductBadges($this->product([
            'product_badge' => 'hot',
            'special_price' => 77,
            'created_at' => date('Y-m-d H:i:s'),
        ]));

        $this->assertCount(1, $badges);
        $this->assertSame('sale', $badges[0]['type']);
        $this->assertSame('sale', $badges[0]['tone']);
        $this->assertSame('-23%', $badges[0]['label']);
    }

    public function testDefaultOrderIsSaleNewStockThenCustomByRulePriority(): void
    {
        $this->enable(['smart_badge/general/max_badges' => 10]);
        $this->qty = 2;
        $this->ruleBadges = [
            ['type' => 'hot', 'label' => 'Low', 'priority' => 10],
            ['type' => 'exclusive', 'label' => 'High', 'priority' => 90],
            ['type' => 'new', 'label' => 'Rule new', 'priority' => 5],
        ];

        $badges = $this->helper()->getProductBadges($this->product([
            'special_price' => 50,
            'created_at' => date('Y-m-d H:i:s'),
        ]));

        $this->assertSame(['-50%', 'Rule new', 'New', 'Only 2 left', 'High', 'Low'], array_column($badges, 'label'));
        $this->assertSame(['sale', 'new', 'new', 'stock', 'custom', 'custom'], array_column($badges, 'tone'));
    }

    public function testConfiguredPriorityOrderIsHonouredAndCompleted(): void
    {
        $this->enable([
            'smart_badge/general/max_badges' => 4,
            'smart_badge/general/priority_order' => ' Stock, custom ,bogus,stock',
        ]);
        $this->qty = 2;
        $this->ruleBadges = [['type' => 'trending', 'label' => 'Trending']];

        $helper = $this->helper();
        $this->assertSame(['stock', 'custom', 'sale', 'new'], $helper->getPriorityOrder());

        $badges = $helper->getProductBadges($this->product([
            'special_price' => 50,
            'created_at' => date('Y-m-d H:i:s'),
        ]));
        $this->assertSame(['stock', 'custom', 'sale', 'new'], array_column($badges, 'tone'));
    }

    public function testEmptyPriorityOrderUsesDefault(): void
    {
        $this->enable(['smart_badge/general/priority_order' => '']);
        $this->assertSame(['sale', 'new', 'stock', 'custom'], $this->helper()->getPriorityOrder());
    }

    public function testToneMapping(): void
    {
        $this->assertSame('sale', BadgeHelper::getTone('SALE'));
        $this->assertSame('new', BadgeHelper::getTone('new'));
        $this->assertSame('stock', BadgeHelper::getTone('stock'));
        $this->assertSame('stock', BadgeHelper::getTone('limited'));
        $this->assertSame('custom', BadgeHelper::getTone('hot'));
        $this->assertSame('custom', BadgeHelper::getTone(''));
    }

    public function testIconsAreRemovedByDefaultAndKeptWhenEnabled(): void
    {
        $this->enable(['smart_badge/general/max_badges' => 5]);
        $this->qty = 2;
        $this->ruleBadges = [['type' => 'hot', 'label' => 'Hot', 'icon' => 'fa-solid fa-fire']];
        $product = $this->product([
            'product_badge' => 'featured',
            'special_price' => 50,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        foreach ($this->helper()->getProductBadges($product) as $badge) {
            $this->assertArrayNotHasKey('icon', $badge);
        }

        $this->config['smart_badge/display/show_icons'] = 1;
        $badges = $this->helper()->getProductBadges($product);
        $icons = array_column($badges, 'icon');
        $this->assertCount(5, $icons);
        $this->assertContains('fa-solid fa-fire', $icons);
        foreach ($icons as $icon) {
            $this->assertMatchesRegularExpression('/^[\x20-\x7E]+$/', $icon);
        }
    }

    public function testLabelsAreShortAndAscii(): void
    {
        $this->enable(['smart_badge/general/max_badges' => 3, 'smart_badge/display/show_icons' => 1]);
        $this->qty = 2;
        $badges = $this->helper()->getProductBadges($this->product([
            'special_price' => 50,
            'created_at' => date('Y-m-d H:i:s'),
        ]));

        $this->assertSame(['-50%', 'New', 'Only 2 left'], array_column($badges, 'label'));
        foreach ($badges as $badge) {
            $this->assertMatchesRegularExpression('/^[\x20-\x7E]+$/', $badge['label'] . $badge['icon']);
        }
    }

    public function testCustomStylingIsStrippedByDefaultAndKeptWhenEnabled(): void
    {
        $this->enable(['smart_badge/general/max_badges' => 2]);
        $this->ruleBadges = [[
            'type' => 'hot',
            'customColor' => '#B91C1C',
            'badge_style' => ['borderRadius' => '12'],
            'animation' => 'pulse',
        ]];
        $product = $this->product(['product_badge' => 'exclusive', 'badge_custom_color' => '#123456']);

        foreach ($this->helper()->getProductBadges($product) as $badge) {
            $this->assertArrayNotHasKey('customColor', $badge);
            $this->assertArrayNotHasKey('badge_style', $badge);
            $this->assertArrayNotHasKey('animation', $badge);
        }

        $this->config['smart_badge/display/use_custom_styling'] = 1;
        $badges = $this->helper()->getProductBadges($product);
        $this->assertSame('#123456', $badges[0]['customColor']);
        $this->assertSame('#B91C1C', $badges[1]['customColor']);
        $this->assertSame(['borderRadius' => '12'], $badges[1]['badge_style']);
        $this->assertSame('pulse', $badges[1]['animation']);
    }

    public function testUnlimitedCallReturnsAllCandidatesInOrder(): void
    {
        $this->enable();
        $this->qty = 2;
        $this->ruleBadges = [['type' => 'hot', 'priority' => 80]];
        $helper = $this->helper();
        $product = $this->product(['special_price' => 50, 'created_at' => date('Y-m-d H:i:s')]);

        $this->assertSame(1, $helper->getMaxBadges());
        $this->assertCount(1, $helper->getProductBadges($product));
        $all = $helper->getProductBadges($product, false);
        $this->assertSame(['sale', 'new', 'stock', 'hot'], array_column($all, 'type'));
        $this->assertSame(80, $all[3]['rule_priority']);
        $this->assertSame(50, $all[3]['priority']);
    }

    public function testMaxBadgesConfig(): void
    {
        $this->enable(['smart_badge/general/max_badges' => '4']);
        $this->assertSame(4, $this->helper()->getMaxBadges());
        $this->config['smart_badge/general/max_badges'] = '0';
        $this->assertSame(1, $this->helper()->getMaxBadges());
        $this->config['smart_badge/general/max_badges'] = '11';
        $this->assertSame(1, $this->helper()->getMaxBadges());
    }
}
