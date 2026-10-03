<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Test\Unit\Helper;

use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
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

    private function helper(): BadgeHelper
    {
        $scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturnCallback(fn($path) => $this->config[$path] ?? null);
        $scopeConfig->method('isSetFlag')->willReturnCallback(fn($path) => (bool)($this->config[$path] ?? false));
        $context = $this->createStub(Context::class);
        $context->method('getScopeConfig')->willReturn($scopeConfig);

        $timezone = $this->createStub(TimezoneInterface::class);
        $timezone->method('isScopeDateInInterval')->willReturnCallback(fn() => $this->inDateInterval);

        $stockItem = $this->createStub(StockItemInterface::class);
        $stockItem->method('getQty')->willReturnCallback(fn() => $this->qty);
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
        $this->enable();
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

        $this->assertSame('LIMITED', $badges[0]['label']);
        $this->assertSame('--badge-limited', $badges[0]['cssVar']);
        $this->assertArrayNotHasKey('customColor', $badges[0]);
    }

    public function testUnknownManualBadgeFallsThroughToRules(): void
    {
        $this->enable();
        $this->ruleBadges = [['type' => 'first'], ['type' => 'second']];
        $badges = $this->helper()->getProductBadges($this->product(['product_badge' => 'bogus']));

        $this->assertSame([['type' => 'first']], $badges);
    }

    public function testPriorityModeAutoBadges(): void
    {
        $this->enable();
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

    public function testOutOfRangeMaxBadgesFallsBackToThree(): void
    {
        $this->enable(['smart_badge/general/max_badges' => 50, 'smart_badge/general/badge_combination_mode' => 'all']);
        $this->ruleBadges = [['type' => 'r1'], ['type' => 'r2'], ['type' => 'r3']];
        $this->config['smart_badge/general/show_multiple_rule_badges'] = 1;
        $this->config['smart_badge/general/auto_badges_with_manual'] = 1;
        $this->qty = 2;

        $badges = $this->helper()->getProductBadges($this->product(['product_badge' => 'new']));
        $this->assertCount(3, $badges);
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
        $this->enable(['smart_badge/general/badge_combination_mode' => 'collect_all']);
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

        $this->assertSame(['featured', 'r1', 'r2', 'stock'], array_column($badges, 'type'));
        $this->assertSame([100, 50, 50, 10], array_column($badges, 'priority'));
    }

    public function testCollectAllModeUsesAutoBadgesWhenNothingElseMatches(): void
    {
        $this->enable(['smart_badge/general/badge_combination_mode' => 'collect_all']);
        $this->qty = 1;

        $badges = $this->helper()->getProductBadges($this->product());

        $this->assertSame(['stock'], array_column($badges, 'type'));
        $this->assertSame(10, $badges[0]['priority']);
    }
}
