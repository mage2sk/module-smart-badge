<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Test\Unit\Controller\Adminhtml\Rule;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Filesystem;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Panth\SmartBadge\Controller\Adminhtml\Rule\Save;
use Panth\SmartBadge\Model\ResourceModel\Rule as RuleResource;
use Panth\SmartBadge\Model\RuleFactory;
use Panth\SmartBadge\Test\Unit\Fixture\BuildsBackendContext;
use Panth\SmartBadge\Test\Unit\Fixture\BuildsRules;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SaveTest extends TestCase
{
    use BuildsBackendContext;
    use BuildsRules;

    private ?array $savedData = null;
    private array $persisted = [];
    private bool $cleared = false;
    private array $existing = [];
    private ?\Throwable $saveException = null;
    private array $files = [];

    private function controller(array $post, array $params = []): Save
    {
        $this->request = $this->createStub(Http::class);
        $this->request->method('getPostValue')->willReturn($post);
        $this->request->method('getParam')->willReturnCallback(
            static fn($key, $default = null) => $params[$key] ?? $default
        );
        $this->request->method('getFiles')->willReturnCallback(fn() => $this->files);

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
            if (!$rule->getId()) {
                $rule->setId(77);
            }
            $this->savedData = $rule->getData();
            return $resource;
        });

        $persistor = $this->createStub(DataPersistorInterface::class);
        $persistor->method('set')->willReturnCallback(function ($key, $value) {
            $this->persisted[$key] = $value;
        });
        $persistor->method('clear')->willReturnCallback(function () {
            $this->cleared = true;
        });

        return new Save(
            $this->backendContext(),
            $ruleFactory,
            $resource,
            $this->jsonFactory(),
            $this->createStub(LoggerInterface::class),
            $this->createStub(Filesystem::class),
            $this->createStub(UploaderFactory::class),
            $persistor
        );
    }

    public function testMissingNameRedirectsToGrid(): void
    {
        $this->controller(['badge_text' => 'x'])->execute();

        $this->assertSame(['Rule name is required.'], $this->messages['error']);
        $this->assertSame('*/*/', $this->redirectPath);
        $this->assertNull($this->savedData);
    }

    public function testNewRuleIsNormalisedAndSaved(): void
    {
        $this->controller([
            'rule_id' => '0',
            'form_key' => 'abc',
            'name' => '  Summer  ',
            'is_active' => 'true',
            'use_same_position' => '0',
            'product_ids' => '1, x,2,,0',
            'category_ids' => ['5', 'z', '6'],
            'store_ids' => [],
            'customer_group_ids' => ['1', 'a', '1', '3'],
            'badge_color' => '#00ff00',
            'badge_style' => ['width' => 10],
            'image_settings' => '{"imageOnly":true}',
            'smart_conditions' => '{broken',
            'schedule_from' => '',
            'position_product' => 'bottom-left',
        ])->execute();

        $data = $this->savedData;
        $this->assertArrayNotHasKey('form_key', $data);
        $this->assertSame(77, $data['rule_id']);
        $this->assertSame('Summer', $data['name']);
        $this->assertSame(1, $data['is_active']);
        $this->assertSame(0, $data['use_same_position']);
        $this->assertSame(50, $data['priority']);
        $this->assertSame('1,2', $data['product_ids']);
        $this->assertSame('5,6', $data['category_ids']);
        $this->assertSame('0', $data['store_ids']);
        $this->assertSame('1,3', $data['customer_group_ids']);
        $this->assertSame('{"width":10}', $data['badge_style']);
        $this->assertSame('{"imageOnly":true}', $data['image_settings']);
        $this->assertNull($data['smart_conditions']);
        $this->assertNull($data['schedule_from']);
        $this->assertNull($data['schedule_to']);
        $this->assertSame('all', $data['display_on']);
        $this->assertSame('bottom-left', $data['position_product']);
        $this->assertSame('top-left', $data['position_category']);
        $this->assertSame(['Badge rule saved successfully.'], $this->messages['success']);
        $this->assertTrue($this->cleared);
        $this->assertSame('*/*/', $this->redirectPath);
    }

    public function testStoreListContainingAllStoresCollapsesToZero(): void
    {
        $this->controller(['name' => 'R', 'store_ids' => '2,0,3'])->execute();
        $this->assertSame('0', $this->savedData['store_ids']);

        $this->controller(['name' => 'R', 'store_ids' => '3, 2,3'])->execute();
        $this->assertSame('3,2', $this->savedData['store_ids']);
    }

    public function testSaveAndContinueRedirectsToEdit(): void
    {
        $this->controller(['name' => 'R', 'priority' => '90'], ['back' => 'edit'])->execute();

        $this->assertSame(90, $this->savedData['priority']);
        $this->assertSame('*/*/edit', $this->redirectPath);
        $this->assertSame(['rule_id' => 77], $this->redirectParams);
    }

    public function testExistingRuleIsMerged(): void
    {
        $this->existing[5] = ['rule_id' => 5, 'name' => 'Old', 'badge_text' => 'Keep'];
        $this->controller(['rule_id' => '5', 'name' => 'New'])->execute();

        $this->assertSame('5', $this->savedData['rule_id']);
        $this->assertSame('New', $this->savedData['name']);
        $this->assertSame('Keep', $this->savedData['badge_text']);
    }

    public function testMissingExistingRuleKeepsUserOnEditPage(): void
    {
        $this->controller(['rule_id' => '404', 'name' => 'Ghost'])->execute();

        $this->assertSame(['Rule not found.'], $this->messages['error']);
        $this->assertNull($this->savedData);
        $this->assertSame('*/*/edit', $this->redirectPath);
        $this->assertSame('404', $this->redirectParams['rule_id']);
        $this->assertSame('Ghost', $this->persisted['smartbadge_rule']['name']);
    }

    public function testInvalidColorIsRejected(): void
    {
        $this->controller(['name' => 'R', 'badge_color' => 'red'])->execute();

        $this->assertStringContainsString('Invalid color format', $this->messages['error'][0]);
        $this->assertNull($this->savedData);
        $this->assertSame('*/*/new', $this->redirectPath);
        $this->assertArrayHasKey('smartbadge_rule', $this->persisted);
    }

    public function testOutOfRangePriorityIsRejected(): void
    {
        $this->controller(['name' => 'R', 'priority' => '101'])->execute();
        $this->assertSame(['Priority must be a number between 0 and 100.'], $this->messages['error']);

        $this->controller(['name' => 'R', 'priority' => 'high'])->execute();
        $this->assertNull($this->savedData);
    }

    public function testUnsafeImageNameIsRejected(): void
    {
        $this->controller(['name' => 'R', 'badge_image' => '../../shell.php'])->execute();

        $this->assertSame(['Invalid badge image file name.'], $this->messages['error']);
        $this->assertNull($this->savedData);
    }

    public function testUploaderStyleImageArrayIsReducedToBasename(): void
    {
        $this->controller(['name' => 'R', 'badge_image' => [['name' => 'dir/promo.webp']]])->execute();
        $this->assertSame('promo.webp', $this->savedData['badge_image']);

        $this->controller(['name' => 'R', 'badge_image' => [['url' => 'x']]])->execute();
        $this->assertNull($this->savedData['badge_image']);
    }

    public function testOversizedUploadIsRejected(): void
    {
        $this->files = ['badge_image' => ['tmp_name' => '/tmp/php123', 'size' => 3 * 1024 * 1024]];
        $this->controller(['name' => 'R'])->execute();

        $this->assertSame(['Image file size must not exceed 2MB.'], $this->messages['error']);
        $this->assertNull($this->savedData);
    }

    public function testUnexpectedSaveErrorIsReported(): void
    {
        $this->saveException = new \RuntimeException('db down');
        $this->controller(['name' => 'R'])->execute();

        $this->assertSame(['An error occurred while saving: db down'], $this->messages['error']);
        $this->assertSame('*/*/new', $this->redirectPath);
    }
}
