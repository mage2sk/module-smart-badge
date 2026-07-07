<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Controller\Adminhtml\Rule;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Framework\App\Request\DataPersistorInterface;
use Panth\SmartBadge\Model\RuleFactory;
use Panth\SmartBadge\Model\ResourceModel\Rule as RuleResource;
use Psr\Log\LoggerInterface;

class Save extends Action
{
    const ADMIN_RESOURCE = 'Panth_SmartBadge::rule_save';
    const BADGE_IMAGE_PATH = 'smartbadge';

    protected $ruleFactory;
    protected $ruleResource;
    protected $jsonFactory;
    protected $logger;
    protected $filesystem;
    protected $uploaderFactory;
    protected $dataPersistor;

    public function __construct(
        Context $context,
        RuleFactory $ruleFactory,
        RuleResource $ruleResource,
        JsonFactory $jsonFactory,
        LoggerInterface $logger,
        Filesystem $filesystem,
        UploaderFactory $uploaderFactory,
        DataPersistorInterface $dataPersistor
    ) {
        parent::__construct($context);
        $this->ruleFactory = $ruleFactory;
        $this->ruleResource = $ruleResource;
        $this->jsonFactory = $jsonFactory;
        $this->logger = $logger;
        $this->filesystem = $filesystem;
        $this->uploaderFactory = $uploaderFactory;
        $this->dataPersistor = $dataPersistor;
    }

    public function execute()
    {
        $data = $this->getRequest()->getPostValue();

        if (!$data || empty($data['name'])) {
            $this->messageManager->addErrorMessage(__('Rule name is required.'));
            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }

        try {
            $imageUploadResult = $this->handleImageUpload();
            if ($imageUploadResult['error']) {
                throw new LocalizedException(__($imageUploadResult['message']));
            }
            if (isset($imageUploadResult['file'])) {
                $data['badge_image'] = $imageUploadResult['file'];
            }

            if (isset($data['badge_image']) && is_array($data['badge_image'])) {
                if (!empty($data['badge_image'][0]['name'])) {
                    $data['badge_image'] = $data['badge_image'][0]['name'];
                } else {
                    $data['badge_image'] = null;
                }
            }

            $validationErrors = $this->validateRuleData($data);
            if (!empty($validationErrors)) {
                throw new LocalizedException(__(implode(' ', $validationErrors)));
            }

            if (isset($data['rule_id']) && ($data['rule_id'] === '' || $data['rule_id'] === null || $data['rule_id'] === '0' || $data['rule_id'] === 0)) {
                unset($data['rule_id']);
            }

            unset($data['form_key']);

            $data = $this->prepareRuleData($data);

            $rule = $this->ruleFactory->create();

            if (!empty($data['rule_id'])) {
                $this->ruleResource->load($rule, (int)$data['rule_id']);
                if (!$rule->getId()) {
                    throw new LocalizedException(__('Rule not found.'));
                }
                $rule->addData($data);
            } else {
                $rule->setData($data);
            }

            $this->ruleResource->save($rule);

            $this->messageManager->addSuccessMessage(__('Badge rule saved successfully.'));
            $this->dataPersistor->clear('smartbadge_rule');

            $resultRedirect = $this->resultRedirectFactory->create();
            if ($this->getRequest()->getParam('back')) {
                return $resultRedirect->setPath('*/*/edit', ['rule_id' => $rule->getId()]);
            }

            return $resultRedirect->setPath('*/*/');
        } catch (LocalizedException $e) {
            $this->logger->error('Badge Rule Save Error: ' . $e->getMessage());
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->error('Badge Rule Save Error: ' . $e->getMessage());
            $this->messageManager->addErrorMessage(__('An error occurred while saving: %1', $e->getMessage()));
        }

        $this->dataPersistor->set('smartbadge_rule', $data);

        $resultRedirect = $this->resultRedirectFactory->create();
        if (!empty($data['rule_id'])) {
            return $resultRedirect->setPath('*/*/edit', ['rule_id' => $data['rule_id']]);
        }
        return $resultRedirect->setPath('*/*/new');
    }

    protected function validateRuleData(array $data): array
    {
        $errors = [];

        if (empty($data['name'])) {
            $errors[] = __('Rule name is required.');
        }

        if (isset($data['priority']) && $data['priority'] !== '' && $data['priority'] !== null) {
            if (!is_numeric($data['priority']) || (int)$data['priority'] < 0 || (int)$data['priority'] > 100) {
                $errors[] = __('Priority must be a number between 0 and 100.');
            }
        }

        return $errors;
    }

    protected function prepareRuleData(array $data): array
    {
        $data['is_active'] = isset($data['is_active']) && (
            $data['is_active'] === true ||
            $data['is_active'] === 'true' ||
            $data['is_active'] === '1' ||
            $data['is_active'] === 1
        ) ? 1 : 0;

        $data['use_same_position'] = isset($data['use_same_position']) && (
            $data['use_same_position'] === true ||
            $data['use_same_position'] === 'true' ||
            $data['use_same_position'] === '1' ||
            $data['use_same_position'] === 1
        ) ? 1 : 0;

        if (isset($data['priority'])) {
            $data['priority'] = (int)$data['priority'];
        } else {
            $data['priority'] = 50;
        }

        if (isset($data['product_ids'])) {
            if (is_array($data['product_ids'])) {
                $data['product_ids'] = implode(',', array_filter(array_map('intval', $data['product_ids'])));
            } else {
                $productIds = array_filter(array_map('trim', explode(',', (string)$data['product_ids'])));
                $productIds = array_filter(array_map('intval', $productIds));
                $data['product_ids'] = implode(',', $productIds);
            }
        } else {
            $data['product_ids'] = '';
        }

        if (isset($data['category_ids'])) {
            if (is_array($data['category_ids'])) {
                $data['category_ids'] = implode(',', array_filter(array_map('intval', $data['category_ids'])));
            } else {
                $categoryIds = array_filter(array_map('trim', explode(',', (string)$data['category_ids'])));
                $categoryIds = array_filter(array_map('intval', $categoryIds));
                $data['category_ids'] = implode(',', $categoryIds);
            }
        } else {
            $data['category_ids'] = '';
        }

        $stringFields = [
            'name', 'badge_type', 'badge_text', 'badge_color', 'badge_icon', 'animation', 'badge_image',
            'display_on', 'position_category', 'position_product', 'position_slider', 'position_all'
        ];
        foreach ($stringFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = trim((string)$data[$field]);
            }
        }

        if (!isset($data['display_on']) || empty($data['display_on'])) {
            $data['display_on'] = 'all';
        }
        if (!isset($data['position_category']) || empty($data['position_category'])) {
            $data['position_category'] = 'top-left';
        }
        if (!isset($data['position_product']) || empty($data['position_product'])) {
            $data['position_product'] = 'top-left';
        }
        if (!isset($data['position_slider']) || empty($data['position_slider'])) {
            $data['position_slider'] = 'top-left';
        }
        if (!isset($data['position_all']) || empty($data['position_all'])) {
            $data['position_all'] = 'top-left';
        }

        if (isset($data['badge_style'])) {
            if (is_string($data['badge_style'])) {
                $decoded = json_decode($data['badge_style'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $data['badge_style'] = null;
                } else {
                    $data['badge_style'] = $data['badge_style'];
                }
            } elseif (is_array($data['badge_style'])) {
                $data['badge_style'] = json_encode($data['badge_style']);
            } else {
                $data['badge_style'] = null;
            }
        } else {
            $data['badge_style'] = null;
        }

        if (isset($data['image_settings'])) {
            if (is_string($data['image_settings'])) {
                $decoded = json_decode($data['image_settings'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $data['image_settings'] = null;
                } else {
                    $data['image_settings'] = $data['image_settings'];
                }
            } elseif (is_array($data['image_settings'])) {
                $data['image_settings'] = json_encode($data['image_settings']);
            } else {
                $data['image_settings'] = null;
            }
        } else {
            $data['image_settings'] = null;
        }

        if (isset($data['smart_conditions'])) {
            if (is_string($data['smart_conditions'])) {
                $decoded = json_decode($data['smart_conditions'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $data['smart_conditions'] = null;
                } else {
                    $data['smart_conditions'] = $data['smart_conditions'];
                }
            } elseif (is_array($data['smart_conditions'])) {
                $data['smart_conditions'] = json_encode($data['smart_conditions']);
            } else {
                $data['smart_conditions'] = null;
            }
        } else {
            $data['smart_conditions'] = null;
        }

        if (isset($data['schedule_from'])) {
            $data['schedule_from'] = !empty($data['schedule_from']) ? $data['schedule_from'] : null;
        } else {
            $data['schedule_from'] = null;
        }

        if (isset($data['schedule_to'])) {
            $data['schedule_to'] = !empty($data['schedule_to']) ? $data['schedule_to'] : null;
        } else {
            $data['schedule_to'] = null;
        }

        return $data;
    }

    protected function handleImageUpload(): array
    {
        try {
            $files = $this->getRequest()->getFiles();

            if (!isset($files['badge_image']) || !isset($files['badge_image']['tmp_name']) || !$files['badge_image']['tmp_name']) {
                return ['error' => false];
            }

            $fileData = $files['badge_image'];

            $maxFileSize = 2 * 1024 * 1024;
            if ($fileData['size'] > $maxFileSize) {
                return [
                    'error' => true,
                    'message' => 'Image file size must not exceed 2MB.'
                ];
            }

            $uploader = $this->uploaderFactory->create(['fileId' => 'badge_image']);

            $uploader->setAllowedExtensions(['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp']);
            $uploader->setAllowRenameFiles(true);
            $uploader->setFilesDispersion(false);

            $mediaDirectory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
            $path = $mediaDirectory->getAbsolutePath(self::BADGE_IMAGE_PATH);

            if (!is_dir($path)) {
                mkdir($path, 0777, true);
            }

            $ruleId = $this->getRequest()->getParam('rule_id');
            if ($ruleId) {
                $rule = $this->ruleFactory->create();
                $this->ruleResource->load($rule, $ruleId);
                if ($rule->getId() && $rule->getData('badge_image')) {
                    $oldImagePath = $mediaDirectory->getAbsolutePath(self::BADGE_IMAGE_PATH . '/' . $rule->getData('badge_image'));
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }
            }

            $result = $uploader->save($path);

            if (!$result) {
                return [
                    'error' => true,
                    'message' => 'Failed to upload image.'
                ];
            }

            return [
                'error' => false,
                'file' => $result['file']
            ];
        } catch (\Exception $e) {
            $this->logger->error('Badge image upload error: ' . $e->getMessage());
            return [
                'error' => true,
                'message' => 'An error occurred while uploading the image: ' . $e->getMessage()
            ];
        }
    }
}
