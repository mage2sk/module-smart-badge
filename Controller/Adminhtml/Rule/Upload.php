<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Controller\Adminhtml\Rule;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Store\Model\StoreManagerInterface;

class Upload extends Action
{
    const ADMIN_RESOURCE = 'Panth_SmartBadge::rule_save';

    private JsonFactory $jsonFactory;
    private Filesystem $filesystem;
    private UploaderFactory $uploaderFactory;
    private StoreManagerInterface $storeManager;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        Filesystem $filesystem,
        UploaderFactory $uploaderFactory,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->filesystem = $filesystem;
        $this->uploaderFactory = $uploaderFactory;
        $this->storeManager = $storeManager;
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

            $uploader = $this->uploaderFactory->create(['fileId' => 'badge_image']);
            $uploader->setAllowedExtensions(['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp']);
            $uploader->setAllowRenameFiles(true);
            $uploader->setFilesDispersion(false);

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
