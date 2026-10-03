<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Test\Unit\Fixture;

use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Panth\SmartBadge\Model\BadgeFactory;
use Panth\SmartBadge\Model\ResourceModel\Rule as RuleResource;
use Panth\SmartBadge\Model\Rule;

trait BuildsRules
{
    private function makeRule(array $data = [], ?BadgeFactory $factory = null): Rule
    {
        $resource = $this->createStub(RuleResource::class);
        $resource->method('getIdFieldName')->willReturn('rule_id');

        return new Rule(
            $this->createStub(Context::class),
            $this->createStub(Registry::class),
            $factory ?? $this->createStub(BadgeFactory::class),
            $resource,
            null,
            $data
        );
    }
}
