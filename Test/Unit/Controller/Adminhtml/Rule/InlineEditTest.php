<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Test\Unit\Controller\Adminhtml\Rule;

use Magento\Framework\App\Request\Http;
use Magento\Framework\Exception\LocalizedException;
use Panth\SmartBadge\Controller\Adminhtml\Rule\InlineEdit;
use Panth\SmartBadge\Model\ResourceModel\Rule as RuleResource;
use Panth\SmartBadge\Model\RuleFactory;
use Panth\SmartBadge\Test\Unit\Fixture\BuildsBackendContext;
use Panth\SmartBadge\Test\Unit\Fixture\BuildsRules;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class InlineEditTest extends TestCase
{
    use BuildsBackendContext;
    use BuildsRules;

    private array $existing = [1 => ['rule_id' => 1, 'name' => 'One', 'priority' => 10, 'badge_type' => 'sale']];
    private array $saved = [];
    private ?\Throwable $saveException = null;

    private function runController(array $items, bool $ajax = true): array
    {
        $this->request = $this->createStub(Http::class);
        $this->request->method('getParam')->willReturnCallback(
            static fn($key, $default = null) => ['items' => $items, 'isAjax' => $ajax][$key] ?? $default
        );

        $ruleFactory = $this->createStub(RuleFactory::class);
        $ruleFactory->method('create')->willReturnCallback(fn() => $this->makeRule());
        $resource = $this->createStub(RuleResource::class);
        $resource->method('load')->willReturnCallback(function ($rule, $id) use ($resource) {
            if (isset($this->existing[(int)$id])) {
                $rule->setData($this->existing[(int)$id]);
            }
            return $resource;
        });
        $resource->method('save')->willReturnCallback(function ($rule) use ($resource) {
            if ($this->saveException) {
                throw $this->saveException;
            }
            $this->saved[$rule->getId()] = $rule->getData();
            return $resource;
        });

        (new InlineEdit(
            $this->backendContext(),
            $this->jsonFactory(),
            $ruleFactory,
            $resource,
            $this->createStub(LoggerInterface::class)
        ))->execute();

        $payload = $this->jsonPayload;
        $payload['messages'] = array_map('strval', $payload['messages']);
        return $payload;
    }

    public function testNonAjaxOrEmptyRequestIsRejected(): void
    {
        $this->assertSame(
            ['messages' => ['Please correct the data sent.'], 'error' => true],
            $this->runController([1 => ['name' => 'x']], false)
        );
        $this->assertTrue($this->runController([])['error']);
    }

    public function testOnlyWhitelistedFieldsAreSaved(): void
    {
        $result = $this->runController([1 => [
            'name' => 'Renamed',
            'priority' => '30',
            'is_active' => '5',
            'badge_type' => 'hot',
            'product_ids' => '1,2',
        ]]);

        $this->assertSame(['messages' => [], 'error' => false], $result);
        $this->assertSame('Renamed', $this->saved[1]['name']);
        $this->assertSame(30, $this->saved[1]['priority']);
        $this->assertSame(1, $this->saved[1]['is_active']);
        $this->assertSame('sale', $this->saved[1]['badge_type']);
        $this->assertArrayNotHasKey('product_ids', $this->saved[1]);
    }

    public function testValidationErrorsPerRow(): void
    {
        $this->existing[2] = ['rule_id' => 2, 'name' => 'Two'];
        $this->existing[3] = ['rule_id' => 3, 'name' => 'Three'];
        $result = $this->runController([
            1 => ['priority' => '500'],
            2 => ['name' => '   '],
            3 => ['badge_color' => 'blue'],
            9 => ['name' => 'Missing'],
        ]);

        $this->assertTrue($result['error']);
        $this->assertCount(4, $result['messages']);
        $this->assertStringContainsString('between 0 and 100 for rule ID "1"', $result['messages'][0]);
        $this->assertStringContainsString('cannot be empty for rule ID "2"', $result['messages'][1]);
        $this->assertStringContainsString('Invalid color format for rule ID "3"', $result['messages'][2]);
        $this->assertSame('Rule with ID "9" does not exist.', $result['messages'][3]);
        $this->assertSame([], $this->saved);
    }

    public function testSaveExceptionsAreReported(): void
    {
        $this->saveException = new LocalizedException(__('locked'));
        $this->assertSame(['[Rule ID: 1] locked'], $this->runController([1 => ['name' => 'x']])['messages']);

        $this->saveException = new \Exception('secret');
        $this->assertSame(
            ['[Rule ID: 1] Something went wrong while saving the rule.'],
            $this->runController([1 => ['name' => 'x']])['messages']
        );
    }
}
