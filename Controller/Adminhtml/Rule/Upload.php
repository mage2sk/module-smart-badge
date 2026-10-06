<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Controller\Adminhtml\Rule;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Store\Model\StoreManagerInterface;
use Panth\Core\Security\UploadExtensionPolicy;

class Upload extends Action implements HttpPostActionInterface
{
    const ADMIN_RESOURCE = 'Panth_SmartBadge::rule_save';

    private JsonFactory $jsonFactory;
    private Filesystem $filesystem;
    private UploaderFactory $uploaderFactory;
    private StoreManagerInterface $storeManager;
    private UploadExtensionPolicy $uploadExtensionPolicy;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        Filesystem $filesystem,
        UploaderFactory $uploaderFactory,
        StoreManagerInterface $storeManager,
        UploadExtensionPolicy $uploadExtensionPolicy
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->filesystem = $filesystem;
        $this->uploaderFactory = $uploaderFactory;
        $this->storeManager = $storeManager;
        $this->uploadExtensionPolicy = $uploadExtensionPolicy;
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();

        try {
            $mediaDir = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
            $targetPath = $mediaDir->getAbsolutePath('smartbadge/');

            if (!$mediaDir->isDirectory('smartbadge')) {
                $mediaDir->create('smartbadge');
            }

            $fileData = $this->getRequest()->getFiles('badge_image');
            if (is_array($fileData) && isset($fileData['name']) && is_string($fileData['name'])) {
                $this->uploadExtensionPolicy->assertSafeExtension($fileData['name']);
            }
            if (is_array($fileData) && isset($fileData['size']) && (int)$fileData['size'] > 2 * 1024 * 1024) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Image file size must not exceed 2MB.')
                );
            }

            $uploader = $this->uploaderFactory->create(['fileId' => 'badge_image']);
            $uploader->setAllowedExtensions(['jpg', 'jpeg', 'png', 'gif', 'webp']);
            $uploader->setAllowRenameFiles(true);
            $uploader->setFilesDispersion(false);
            if (!$uploader->checkMimeType(['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Only JPG, PNG, GIF and WebP images are allowed.')
                );
            }

            $uploadResult = $uploader->save($targetPath);

            if (!$uploadResult) {
                throw new \Exception('File upload failed.');
            }

            $baseUrl = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);

            $result->setData([
                'name' => $uploadResult['file'],
                'url' => $baseUrl . 'smartbadge/' . $uploadResult['file'],
                'type' => $uploadResult['type'] ?? 'image/png',
                'size' => $uploadResult['size'] ?? 0,
            ]);
        } catch (\Exception $e) {
            $result->setData([
                'error' => $e->getMessage(),
                'errorcode' => $e->getCode(),
            ]);
        }

        return $result;
    }
}
