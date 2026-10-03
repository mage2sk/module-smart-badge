<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Test\Unit\Block;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template\Context;
use Panth\SmartBadge\Block\Badge;
use Panth\SmartBadge\Helper\BadgeHelper;
use PHPUnit\Framework\TestCase;

class BadgeTest extends TestCase
{
    private BadgeHelper $helper;
    private Registry $registry;
    private array $config = [];

    protected function setUp(): void
    {
        $this->helper = $this->createStub(BadgeHelper::class);
        $this->registry = $this->createStub(Registry::class);
    }

    private function block(): Badge
    {
        $scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturnCallback(fn($path) => $this->config[$path] ?? null);
        $context = $this->createStub(Context::class);
        $context->method('getScopeConfig')->willReturn($scopeConfig);

        return new Badge($context, $this->helper, $this->registry);
    }

    public function testGetBadgesDelegatesOnlyForAProduct(): void
    {
        $product = new DataObject(['id' => 5]);
        $this->helper->method('getProductBadges')->willReturn([['type' => 'new']]);
        $block = $this->block();

        $this->assertSame([['type' => 'new']], $block->getBadges($product));
        $this->assertSame([], $block->getBadges(null));
    }

    public function testIsEnabledDelegatesToHelper(): void
    {
        $this->helper->method('isEnabled')->willReturn(true);
        $this->assertTrue($this->block()->isEnabled());
    }

    public function testCurrentProductIdRequiresProductInterface(): void
    {
        $product = $this->createStub(ProductInterface::class);
        $product->method('getId')->willReturn('42');
        $this->registry->method('registry')->willReturnMap([['current_product', $product]]);

        $block = $this->block();
        $this->assertSame(42, $block->getCurrentProductId());
        $this->assertSame($product, $block->getProduct());
    }

    public function testCurrentProductIdIsZeroForNonProduct(): void
    {
        $this->registry->method('registry')->willReturn(new DataObject(['id' => 3]));
        $this->assertSame(0, $this->block()->getCurrentProductId());
    }

    public function testExplicitProductOverridesRegistry(): void
    {
        $this->registry->method('registry')->willReturn('registry-product');
        $block = $this->block();
        $explicit = new DataObject(['id' => 1]);

        $this->assertSame($block, $block->setProduct($explicit));
        $this->assertSame($explicit, $block->getProduct());
    }

    public function testLayoutAndSpacingConfigWithDefaults(): void
    {
        $block = $this->block();
        $this->assertSame('vertical', $block->getBadgeLayout());
        $this->assertSame('gap-2', $block->getBadgeSpacing());

        $this->config = [
            'smart_badge/display/badge_layout' => 'horizontal',
            'smart_badge/display/badge_spacing' => 'gap-1',
        ];
        $this->assertSame('horizontal', $block->getBadgeLayout());
        $this->assertSame('gap-1', $block->getBadgeSpacing());
    }
}
