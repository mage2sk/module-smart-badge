<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Test\Unit\Controller\Adminhtml\Rule;

use Magento\Framework\App\Request\Http;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\MediaStorage\Model\File\Uploader;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Panth\Core\Security\UploadExtensionPolicy;
use Panth\SmartBadge\Controller\Adminhtml\Rule\Upload;
use Panth\SmartBadge\Test\Unit\Fixture\BuildsBackendContext;
use PHPUnit\Framework\TestCase;

class UploadTest extends TestCase
{
    use BuildsBackendContext;

    private Uploader $uploader;
    private UploadExtensionPolicy $policy;

    protected function setUp(): void
    {
        $this->uploader = $this->createStub(Uploader::class);
        $this->policy = $this->createStub(UploadExtensionPolicy::class);
    }

    private function runUpload(array $file): array
    {
        $this->request = $this->createStub(Http::class);
        $this->request->method('getFiles')->willReturn($file);

        $directory = $this->createStub(WriteInterface::class);
        $directory->method('getAbsolutePath')->willReturn('/media/smartbadge/');
        $directory->method('isDirectory')->willReturn(true);
        $filesystem = $this->createStub(Filesystem::class);
        $filesystem->method('getDirectoryWrite')->willReturn($directory);

        $uploaderFactory = $this->createStub(UploaderFactory::class);
        $uploaderFactory->method('create')->willReturn($this->uploader);

        $store = $this->createStub(Store::class);
        $store->method('getBaseUrl')->willReturn('https://shop.test/media/');
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        (new Upload(
            $this->backendContext(),
            $this->jsonFactory(),
            $filesystem,
            $uploaderFactory,
            $storeManager,
            $this->policy
        ))->execute();

        return $this->jsonPayload;
    }

    public function testSuccessfulUploadReturnsMediaUrl(): void
    {
        $this->uploader->method('checkMimeType')->willReturn(true);
        $this->uploader->method('save')->willReturn(['file' => 'badge_1.png', 'size' => 99]);

        $this->assertSame([
            'name' => 'badge_1.png',
            'url' => 'https://shop.test/media/smartbadge/badge_1.png',
            'type' => 'image/png',
            'size' => 99,
        ], $this->runUpload(['name' => 'badge.png', 'size' => 99]));
    }

    public function testOversizedFileIsRejected(): void
    {
        $result = $this->runUpload(['name' => 'big.png', 'size' => 2 * 1024 * 1024 + 1]);
        $this->assertSame('Image file size must not exceed 2MB.', $result['error']);
    }

    public function testDeniedExtensionIsRejected(): void
    {
        $this->policy->method('assertSafeExtension')->willThrowException(new LocalizedException(__('Denied')));
        $this->assertSame('Denied', $this->runUpload(['name' => 'x.phtml', 'size' => 1])['error']);
    }

    public function testWrongMimeTypeIsRejected(): void
    {
        $this->uploader->method('checkMimeType')->willReturn(false);
        $this->assertSame(
            'Only JPG, PNG, GIF and WebP images are allowed.',
            $this->runUpload(['name' => 'x.png', 'size' => 1])['error']
        );
    }

    public function testFailedSaveIsReported(): void
    {
        $this->uploader->method('checkMimeType')->willReturn(true);
        $this->uploader->method('save')->willReturn(false);
        $this->assertSame('File upload failed.', $this->runUpload(['name' => 'x.png', 'size' => 1])['error']);
    }
}
