<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Model\Rule;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Panth\SmartBadge\Model\ResourceModel\Rule\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\UrlInterface;

class DataProvider extends AbstractDataProvider
{
    protected $collection;
    protected $dataPersistor;
    protected $loadedData;
    protected $storeManager;
    protected $request;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        DataPersistorInterface $dataPersistor,
        StoreManagerInterface $storeManager,
        RequestInterface $request,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->dataPersistor = $dataPersistor;
        $this->storeManager = $storeManager;
        $this->request = $request;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        // Filter by rule_id if editing an existing rule
        $ruleId = $this->request->getParam('rule_id');
        if ($ruleId) {
            $this->collection->addFieldToFilter('rule_id', $ruleId);
        }

        $items = $this->collection->getItems();
        foreach ($items as $rule) {
            $ruleData = $rule->getData();

            // Decode JSON fields
            $ruleData = $this->decodeJsonFields($ruleData);

            // Format dates for form
            $ruleData = $this->formatDates($ruleData);

            // Format badge image
            if (isset($ruleData['badge_image']) && $ruleData['badge_image']) {
                $ruleData['badge_image'] = $this->formatBadgeImage($ruleData['badge_image']);
            }

            $this->loadedData[$rule->getId()] = $ruleData;
        }

        // Check if there's data from previous form submission (in case of validation errors)
        $data = $this->dataPersistor->get('smartbadge_rule');
        if (!empty($data)) {
            $rule = $this->collection->getNewEmptyItem();
            $rule->setData($data);
            $this->loadedData[$rule->getId()] = $rule->getData();
            $this->dataPersistor->clear('smartbadge_rule');
        }

        return $this->loadedData ?? [];
    }

    /**
     * Decode JSON fields from database
     *
     * @param array $data
     * @return array
     */
    protected function decodeJsonFields(array $data): array
    {
        // These fields stay as decoded arrays for the Alpine.js builder
        $arrayFields = ['image_settings'];

        foreach ($arrayFields as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $decoded = json_decode($data[$field], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $data[$field] = $decoded;
                } else {
                    $data[$field] = null;
                }
            }
        }

        // These fields stay as JSON strings for the UI Component textarea
        $jsonStringFields = ['smart_conditions', 'badge_style'];
        foreach ($jsonStringFields as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                // Pretty-print JSON for readability in textarea
                $decoded = json_decode($data[$field], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $data[$field] = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                }
            } elseif (isset($data[$field]) && is_array($data[$field])) {
                // Already decoded array — convert back to pretty JSON string
                $data[$field] = json_encode($data[$field], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            }
        }

        return $data;
    }

    /**
     * Format dates for the form
     *
     * @param array $data
     * @return array
     */
    protected function formatDates(array $data): array
    {
        $dateFields = ['schedule_from', 'schedule_to'];

        foreach ($dateFields as $field) {
            if (isset($data[$field]) && $data[$field]) {
                // Convert MySQL datetime to format expected by UI component
                // Magento UI date component expects: YYYY-MM-DD HH:MM:SS
                $data[$field] = $data[$field];
            }
        }

        return $data;
    }

    /**
     * Format badge image for image uploader component
     *
     * @param string $imageName
     * @return array
     */
    protected function formatBadgeImage(string $imageName): array
    {
        $imageUrl = $this->storeManager->getStore()
            ->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . 'smartbadge/' . $imageName;

        return [
            [
                'name' => $imageName,
                'url' => $imageUrl,
                'size' => $this->getImageSize($imageName),
                'type' => $this->getImageMimeType($imageName)
            ]
        ];
    }

    /**
     * Get image file size
     *
     * @param string $imageName
     * @return int
     */
    protected function getImageSize(string $imageName): int
    {
        try {
            $mediaPath = $this->storeManager->getStore()
                ->getBaseMediaDir() . '/smartbadge/' . $imageName;

            if (file_exists($mediaPath)) {
                return filesize($mediaPath);
            }
        } catch (\Exception $e) {
            // Return 0 if file doesn't exist or error occurs
        }

        return 0;
    }

    /**
     * Get image MIME type
     *
     * @param string $imageName
     * @return string
     */
    protected function getImageMimeType(string $imageName): string
    {
        $extension = pathinfo($imageName, PATHINFO_EXTENSION);

        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'webp' => 'image/webp'
        ];

        return $mimeTypes[strtolower($extension)] ?? 'image/jpeg';
    }
}
