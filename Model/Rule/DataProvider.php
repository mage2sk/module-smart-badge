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

    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        $ruleId = $this->request->getParam('rule_id');
        if ($ruleId) {
            $this->collection->addFieldToFilter('rule_id', $ruleId);
        }

        $items = $this->collection->getItems();
        foreach ($items as $rule) {
            $ruleData = $rule->getData();

            $ruleData = $this->decodeJsonFields($ruleData);

            $ruleData = $this->formatDates($ruleData);

            if (isset($ruleData['badge_image']) && $ruleData['badge_image']) {
                $ruleData['badge_image'] = $this->formatBadgeImage($ruleData['badge_image']);
            }

            $this->loadedData[$rule->getId()] = $ruleData;
        }

        $data = $this->dataPersistor->get('smartbadge_rule');
        if (!empty($data)) {
            $rule = $this->collection->getNewEmptyItem();
            $rule->setData($data);
            $this->loadedData[$rule->getId()] = $rule->getData();
            $this->dataPersistor->clear('smartbadge_rule');
        }

        return $this->loadedData ?? [];
    }

    protected function decodeJsonFields(array $data): array
    {
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

        $jsonStringFields = ['smart_conditions', 'badge_style'];
        foreach ($jsonStringFields as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $decoded = json_decode($data[$field], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $data[$field] = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                }
            } elseif (isset($data[$field]) && is_array($data[$field])) {
                $data[$field] = json_encode($data[$field], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            }
        }

        return $data;
    }

    protected function formatDates(array $data): array
    {
        $dateFields = ['schedule_from', 'schedule_to'];

        foreach ($dateFields as $field) {
            if (isset($data[$field]) && $data[$field]) {
                $data[$field] = $data[$field];
            }
        }

        return $data;
    }

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

    protected function getImageSize(string $imageName): int
    {
        try {
            $mediaPath = $this->storeManager->getStore()
                ->getBaseMediaDir() . '/smartbadge/' . $imageName;

            if (file_exists($mediaPath)) {
                return filesize($mediaPath);
            }
        } catch (\Exception $e) {
        }

        return 0;
    }

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
