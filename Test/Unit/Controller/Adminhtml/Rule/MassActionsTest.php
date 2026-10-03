<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Test\Unit\Controller\Adminhtml\Rule;

use Magento\Framework\App\Request\Http;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\MassAction\Filter;
use Panth\SmartBadge\Controller\Adminhtml\Rule\MassDelete;
use Panth\SmartBadge\Controller\Adminhtml\Rule\MassStatus;
use Panth\SmartBadge\Model\ResourceModel\Rule as RuleResource;
use Panth\SmartBadge\Model\ResourceModel\Rule\Collection;
use Panth\SmartBadge\Model\ResourceModel\Rule\CollectionFactory;
use Panth\SmartBadge\Test\Unit\Fixture\BuildsBackendContext;
use Panth\SmartBadge\Test\Unit\Fixture\BuildsRules;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class MassActionsTest extends TestCase
{
    use BuildsBackendContext;
    use BuildsRules;

    private array $failIds = [];
    private array $touched = [];

    private function filter(array $rules, bool $throws = false): Filter
    {
        $collection = $this->createStub(Collection::class);
        $collection->method('getSize')->willReturn(count($rules));
        $collection->method('getIterator')->willReturn(new \ArrayIterator($rules));
        $filter = $this->createStub(Filter::class);
        if ($throws) {
            $filter->method('getCollection')->willThrowException(new LocalizedException(__('Select rules')));
        } else {
            $filter->method('getCollection')->willReturn($collection);
        }
        return $filter;
    }

    private function collectionFactory(): CollectionFactory
    {
        $factory = $this->createStub(CollectionFactory::class);
        $factory->method('create')->willReturn($this->createStub(Collection::class));
        return $factory;
    }

    private function resource(): RuleResource
    {
        $resource = $this->createStub(RuleResource::class);
        $callback = function ($rule) use ($resource) {
            if (in_array((int)$rule->getId(), $this->failIds, true)) {
                throw new \RuntimeException('fail');
            }
            $this->touched[(int)$rule->getId()] = $rule->getData('is_active');
            return $resource;
        };
        $resource->method('save')->willReturnCallback($callback);
        $resource->method('delete')->willReturnCallback($callback);
        return $resource;
    }

    private function rules(int ...$ids): array
    {
        return array_map(fn($id) => $this->makeRule(['rule_id' => $id, 'is_active' => 1]), $ids);
    }

    private function massStatus(array $rules, $status, bool $throws = false): void
    {
        $this->request = $this->createStub(Http::class);
        $this->request->method('getParam')->willReturn($status);
        (new MassStatus(
            $this->backendContext(),
            $this->filter($rules, $throws),
            $this->collectionFactory(),
            $this->resource(),
            $this->createStub(LoggerInterface::class)
        ))->execute();
    }

    private function massDelete(array $rules, bool $throws = false): void
    {
        (new MassDelete(
            $this->backendContext(),
            $this->filter($rules, $throws),
            $this->collectionFactory(),
            $this->resource(),
            $this->createStub(LoggerInterface::class)
        ))->execute();
    }

    public function testMassStatusRejectsInvalidStatus(): void
    {
        $this->massStatus($this->rules(1), '7');

        $this->assertStringContainsString('Invalid status value', $this->messages['error'][0]);
        $this->assertSame([], $this->touched);
        $this->assertSame('*/*/', $this->redirectPath);
    }

    public function testMassStatusRejectsMissingOrNonNumericStatus(): void
    {
        foreach ([null, '', 'abc', ['1']] as $status) {
            $this->messages = ['error' => [], 'success' => [], 'warning' => []];
            $this->touched = [];
            $this->massStatus($this->rules(1), $status);

            $this->assertStringContainsString('Invalid status value', $this->messages['error'][0]);
            $this->assertSame([], $this->touched);
        }
    }

    public function testMassStatusDisablesRulesAndCountsFailures(): void
    {
        $this->failIds = [2];
        $this->massStatus($this->rules(1, 2, 3), '0');

        $this->assertSame([1 => 0, 3 => 0], $this->touched);
        $this->assertSame(['A total of 2 badge rule(s) have been disabled.'], $this->messages['success']);
        $this->assertStringContainsString('Failed to update 1 badge rule(s)', $this->messages['error'][0]);
    }

    public function testMassStatusWithEmptySelectionWarns(): void
    {
        $this->massStatus([], '1');
        $this->assertSame(['No badge rules were updated.'], $this->messages['warning']);
    }

    public function testMassStatusFilterErrorIsShown(): void
    {
        $this->massStatus([], '1', true);
        $this->assertSame(['Select rules'], $this->messages['error']);
        $this->assertSame('*/*/', $this->redirectPath);
    }

    public function testMassDeleteCountsDeletedAndFailed(): void
    {
        $this->failIds = [5];
        $this->massDelete($this->rules(4, 5));

        $this->assertSame([4], array_keys($this->touched));
        $this->assertSame(['A total of 1 badge rule(s) have been deleted.'], $this->messages['success']);
        $this->assertStringContainsString('Failed to delete 1 badge rule(s)', $this->messages['error'][0]);
        $this->assertSame('*/*/', $this->redirectPath);
    }

    public function testMassDeleteEmptyAndFilterError(): void
    {
        $this->massDelete([]);
        $this->assertSame(['No badge rules were deleted.'], $this->messages['warning']);

        $this->massDelete([], true);
        $this->assertSame(['Select rules'], $this->messages['error']);
    }
}
