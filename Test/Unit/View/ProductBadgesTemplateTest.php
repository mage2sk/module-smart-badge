<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Test\Unit\View;

use PHPUnit\Framework\TestCase;

class ProductBadgesTemplateTest extends TestCase
{
    private function template(): string
    {
        $path = dirname(__DIR__, 3) . '/view/frontend/templates/product-badges.phtml';
        $this->assertTrue(is_file($path));
        return (string)file_get_contents($path);
    }

    public function testNoBadgeAnimationLoopsForever(): void
    {
        $this->assertStringNotContainsString('infinite', $this->template());
    }

    public function testEveryBadgeAnimationStopsWithinFiveSeconds(): void
    {
        preg_match_all(
            '/^\.smart-badge\.(\w+) \{ animation: sb-\w+ ([\d.]+)s [\w-]+ (\d+); \}$/m',
            $this->template(),
            $matches,
            PREG_SET_ORDER
        );

        $this->assertCount(24, $matches);
        foreach ($matches as $match) {
            $iterations = (int)$match[3];
            $this->assertGreaterThanOrEqual(1, $iterations, $match[1]);
            $this->assertLessThanOrEqual(3, $iterations, $match[1]);
            $this->assertLessThanOrEqual(5.0, (float)$match[2] * $iterations, $match[1]);
        }
    }

    public function testReducedMotionSwitchesAnimationsOff(): void
    {
        $template = $this->template();
        $this->assertStringContainsString('@media (prefers-reduced-motion: reduce)', $template);
        $this->assertMatchesRegularExpression(
            '/prefers-reduced-motion: reduce\)\s*\{[^}]*\.smart-badge[^{]*\{\s*animation: none !important;/s',
            $template
        );
    }

    public function testTemplateIsAsciiWithoutEmojiFallbackIcon(): void
    {
        $template = $this->template();
        $this->assertDoesNotMatchRegularExpression('/[^\x00-\x7F]/', $template);
        $this->assertStringContainsString("} else if (iconValue) {", $template);
    }

    public function testBadgesAreLimitedOnTheClient(): void
    {
        $template = $this->template();
        $this->assertStringContainsString('maxBadges: <?= (int)$block->getMaxBadges() ?>', $template);
        $this->assertStringContainsString('.slice(0, maxBadges)', $template);
    }

    public function testSpecSizeAndSemanticColoursAreTheFallbacks(): void
    {
        $template = $this->template();
        $this->assertStringContainsString('height: var(--sb-badge-height, 22px);', $template);
        $this->assertStringContainsString('border-radius: var(--sb-badge-radius, 4px);', $template);
        $this->assertStringContainsString('font-size: var(--sb-badge-font-size, 12px);', $template);
        $this->assertStringContainsString('font-weight: var(--sb-badge-font-weight, 600);', $template);
        $this->assertStringContainsString("sale: 'var(--sb-badge-sale-bg, #B91C1C)'", $template);
        $this->assertStringContainsString("new: 'var(--sb-badge-new-bg, #0F766E)'", $template);
        $this->assertStringContainsString("stock: 'var(--sb-badge-stock-bg, #B45309)'", $template);
        $this->assertStringContainsString('text-overflow: ellipsis;', $template);
    }

    public function testQuickViewModalGetsBadges(): void
    {
        $template = $this->template();
        $this->assertStringContainsString("addEventListener('open-quick-view'", $template);
        $this->assertStringContainsString("document.querySelector('.qv-modal .qv-img-main')", $template);
    }
}
