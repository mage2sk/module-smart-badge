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
            // Handle image upload if file was uploaded directly
            $imageUploadResult = $this->handleImageUpload();
            if ($imageUploadResult['error']) {
                throw new LocalizedException(__($imageUploadResult['message']));
            }
            if (isset($imageUploadResult['file'])) {
                $data['badge_image'] = $imageUploadResult['file'];
            }

            // Handle badge_image from UI Component imageUploader
            if (isset($data['badge_image']) && is_array($data['badge_image'])) {
                if (!empty($data['badge_image'][0]['name'])) {
                    $data['badge_image'] = $data['badge_image'][0]['name'];
                } else {
                    $data['badge_image'] = null;
                }
            }

            // Validate required fields
            $validationErrors = $this->validateRuleData($data);
            if (!empty($validationErrors)) {
                throw new LocalizedException(__(implode(' ', $validationErrors)));
            }

            // Clean empty rule_id — empty string means NEW, must be null for auto-increment
            if (isset($data['rule_id']) && ($data['rule_id'] === '' || $data['rule_id'] === null || $data['rule_id'] === '0' || $data['rule_id'] === 0)) {
                unset($data['rule_id']);
            }

            // Remove form_key from data (not a DB column)
            unset($data['form_key']);

            // Prepare data for saving
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

        // Persist data so the form is pre-filled after redirect
        $this->dataPersistor->set('smartbadge_rule', $data);

        $resultRedirect = $this->resultRedirectFactory->create();
        if (!empty($data['rule_id'])) {
            return $resultRedirect->setPath('*/*/edit', ['rule_id' => $data['rule_id']]);
        }
        return $resultRedirect->setPath('*/*/new');
    }

    /**
     * Validate rule data
     *
     * @param array $data
     * @return array
     */
    protected function validateRuleData(array $data): array
    {
        $errors = [];

        if (empty($data['name'])) {
            $errors[] = __('Rule name is required.');
        }

        // Priority is optional — empty defaults to 50 in prepareRuleData
        if (isset($data['priority']) && $data['priority'] !== '' && $data['priority'] !== null) {
            if (!is_numeric($data['priority']) || (int)$data['priority'] < 0 || (int)$data['priority'] > 100) {
                $errors[] = __('Priority must be a number between 0 and 100.');
            }
        }

        return $errors;
    }

    /**
     * Prepare rule data for saving
     *
     * @param array $data
     * @return array
     */
    protected function prepareRuleData(array $data): array
    {
        // Convert boolean values
        $data['is_active'] = isset($data['is_active']) && (
            $data['is_active'] === true ||
            $data['is_active'] === 'true' ||
            $data['is_active'] === '1' ||
            $data['is_active'] === 1
        ) ? 1 : 0;

        // Convert use_same_position boolean
        $data['use_same_position'] = isset($data['use_same_position']) && (
            $data['use_same_position'] === true ||
            $data['use_same_position'] === 'true' ||
            $data['use_same_position'] === '1' ||
            $data['use_same_position'] === 1
        ) ? 1 : 0;

        // Ensure priority is an integer
        if (isset($data['priority'])) {
            $data['priority'] = (int)$data['priority'];
        } else {
            $data['priority'] = 50; // Default priority
        }

        // Clean and validate product_ids
        if (isset($data['product_ids'])) {
            if (is_array($data['product_ids'])) {
                $data['product_ids'] = implode(',', array_filter(array_map('intval', $data['product_ids'])));
            } else {
                // Clean the comma-separated string
                $productIds = array_filter(array_map('trim', explode(',', (string)$data['product_ids'])));
                $productIds = array_filter(array_map('intval', $productIds));
                $data['product_ids'] = implode(',', $productIds);
            }
        } else {
            $data['product_ids'] = '';
        }

        // Clean and validate category_ids
        if (isset($data['category_ids'])) {
            if (is_array($data['category_ids'])) {
                $data['category_ids'] = implode(',', array_filter(array_map('intval', $data['category_ids'])));
            } else {
                // Clean the comma-separated string
                $categoryIds = array_filter(array_map('trim', explode(',', (string)$data['category_ids'])));
                $categoryIds = array_filter(array_map('intval', $categoryIds));
                $data['category_ids'] = implode(',', $categoryIds);
            }
        } else {
            $data['category_ids'] = '';
        }

        // Trim string fields
        $stringFields = [
            'name', 'badge_type', 'badge_text', 'badge_color', 'badge_icon', 'animation', 'badge_image',
            'display_on', 'position_category', 'position_product', 'position_slider', 'position_all'
        ];
        foreach ($stringFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = trim((string)$data[$field]);
            }
        }

        // Set defaults for position fields if not provided
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

        // Handle JSON fields - badge_style
        if (isset($data['badge_style'])) {
            // If it's already a string (JSON), validate it
            if (is_string($data['badge_style'])) {
                $decoded = json_decode($data['badge_style'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    // Invalid JSON, set to null
                    $data['badge_style'] = null;
                } else {
                    // Valid JSON, keep as-is
                    $data['badge_style'] = $data['badge_style'];
                }
            } elseif (is_array($data['badge_style'])) {
                // Convert array to JSON string
                $data['badge_style'] = json_encode($data['badge_style']);
            } else {
                $data['badge_style'] = null;
            }
        } else {
            $data['badge_style'] = null;
        }

        // Handle JSON fields - image_settings
        if (isset($data['image_settings'])) {
            // If it's already a string (JSON), validate it
            if (is_string($data['image_settings'])) {
                $decoded = json_decode($data['image_settings'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    // Invalid JSON, set to null
                    $data['image_settings'] = null;
                } else {
                    // Valid JSON, keep as-is
                    $data['image_settings'] = $data['image_settings'];
                }
            } elseif (is_array($data['image_settings'])) {
                // Convert array to JSON string
                $data['image_settings'] = json_encode($data['image_settings']);
            } else {
                $data['image_settings'] = null;
            }
        } else {
            $data['image_settings'] = null;
        }

        // Handle JSON fields - smart_conditions (already exists, but ensure proper handling)
        if (isset($data['smart_conditions'])) {
            // If it's already a string (JSON), validate it
            if (is_string($data['smart_conditions'])) {
                $decoded = json_decode($data['smart_conditions'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    // Invalid JSON, set to null
                    $data['smart_conditions'] = null;
                } else {
                    // Valid JSON, keep as-is
                    $data['smart_conditions'] = $data['smart_conditions'];
                }
            } elseif (is_array($data['smart_conditions'])) {
                // Convert array to JSON string
                $data['smart_conditions'] = json_encode($data['smart_conditions']);
            } else {
                $data['smart_conditions'] = null;
            }
        } else {
            $data['smart_conditions'] = null;
        }

        // Handle schedule dates - ensure proper format or null
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

    /**
     * Handle badge image upload
     *
     * @return array
     */
    protected function handleImageUpload(): array
    {
        try {
            $files = $this->getRequest()->getFiles();

            // Check if image was uploaded
            if (!isset($files['badge_image']) || !isset($files['badge_image']['tmp_name']) || !$files['badge_image']['tmp_name']) {
                return ['error' => false];
            }

            $fileData = $files['badge_image'];

            // Validate file size (2MB max)
            $maxFileSize = 2 * 1024 * 1024; // 2MB in bytes
            if ($fileData['size'] > $maxFileSize) {
                return [
                    'error' => true,
                    'message' => 'Image file size must not exceed 2MB.'
                ];
            }

            // Initialize uploader
            $uploader = $this->uploaderFactory->create(['fileId' => 'badge_image']);

            // Set allowed file extensions
            $uploader->setAllowedExtensions(['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp']);
            $uploader->setAllowRenameFiles(true);
            $uploader->setFilesDispersion(false);

            // Get media directory path
            $mediaDirectory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
            $path = $mediaDirectory->getAbsolutePath(self::BADGE_IMAGE_PATH);

            // Create directory if it doesn't exist
            if (!is_dir($path)) {
                mkdir($path, 0777, true);
            }

            // Delete old image if exists and updating existing rule
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

            // Upload the file
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
