<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Test\Unit\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Panth\SmartBadge\Helper\Data;
use PHPUnit\Framework\TestCase;

class DataTest extends TestCase
{
    public function testIsEnabledReadsStoreScopedFlag(): void
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->expects($this->exactly(2))->method('getValue')
            ->with('smart_badge/general/enabled', ScopeInterface::SCOPE_STORE, $this->anything())
            ->willReturnCallback(static fn($path, $scope, $storeId) => $storeId === 2 ? '1' : '0');
        $context = $this->createStub(Context::class);
        $context->method('getScopeConfig')->willReturn($scopeConfig);

        $helper = new Data($context);

        $this->assertTrue($helper->isEnabled(2));
        $this->assertFalse($helper->isEnabled(3));
    }
}
