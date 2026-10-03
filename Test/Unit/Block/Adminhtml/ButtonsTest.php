<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Test\Unit\Block\Adminhtml;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Panth\SmartBadge\Block\Adminhtml\Rule\Edit\BackButton;
use Panth\SmartBadge\Block\Adminhtml\Rule\Edit\DeleteButton;
use Panth\SmartBadge\Block\Adminhtml\Rule\Edit\SaveAndContinueButton;
use Panth\SmartBadge\Block\Adminhtml\Rule\Edit\SaveButton;
use PHPUnit\Framework\TestCase;

class ButtonsTest extends TestCase
{
    private function context($ruleId): Context
    {
        $request = $this->createStub(RequestInterface::class);
        $request->method('getParam')->willReturn($ruleId);
        $url = $this->createStub(UrlInterface::class);
        $url->method('getUrl')->willReturnCallback(
            static fn($route, $params = []) => $route . ($params ? '?' . http_build_query($params) : '')
        );
        $context = $this->createStub(Context::class);
        $context->method('getRequest')->willReturn($request);
        $context->method('getUrlBuilder')->willReturn($url);
        return $context;
    }

    public function testDeleteButtonHiddenForNewRule(): void
    {
        $this->assertSame([], (new DeleteButton($this->context(null)))->getButtonData());
    }

    public function testDeleteButtonTargetsCurrentRule(): void
    {
        $button = new DeleteButton($this->context('12'));
        $data = $button->getButtonData();

        $this->assertSame('*/*/delete?rule_id=12', $button->getDeleteUrl());
        $this->assertSame('Delete', (string)$data['label']);
        $this->assertStringContainsString('*/*/delete?rule_id=12', $data['on_click']);
        $this->assertStringContainsString('deleteConfirm(', $data['on_click']);
        $this->assertSame(20, $data['sort_order']);
    }

    public function testOtherButtonsProvideLabels(): void
    {
        foreach ([BackButton::class, SaveButton::class, SaveAndContinueButton::class] as $class) {
            $data = (new $class($this->context('1')))->getButtonData();
            $this->assertNotEmpty($data['label'], $class);
            $this->assertArrayHasKey('sort_order', $data, $class);
        }
    }

    public function testBackButtonLinksToGrid(): void
    {
        $data = (new BackButton($this->context(null)))->getButtonData();
        $this->assertStringContainsString('*/*/', $data['on_click']);
    }
}
