<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Block\Adminhtml\Rule;

use Magento\Backend\Block\Template;
use Magento\Framework\Data\Form\FormKey;
use Panth\SmartBadge\Model\RuleFactory;
use Panth\SmartBadge\Model\ResourceModel\Rule as RuleResource;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as AttributeCollectionFactory;

class Edit extends Template
{
    protected $_template = 'Panth_SmartBadge::rule/builder.phtml';

    private $ruleFactory;
    private $ruleResource;
    private $categoryCollectionFactory;
    private $storeManager;
    private $attributeCollectionFactory;
    protected $formKey;

    public function __construct(
        Template\Context $context,
        RuleFactory $ruleFactory,
        RuleResource $ruleResource,
        CategoryCollectionFactory $categoryCollectionFactory,
        StoreManagerInterface $storeManager,
        AttributeCollectionFactory $attributeCollectionFactory,
        FormKey $formKey,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->ruleFactory = $ruleFactory;
        $this->ruleResource = $ruleResource;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->storeManager = $storeManager;
        $this->attributeCollectionFactory = $attributeCollectionFactory;
        $this->formKey = $formKey;
    }

    public function getBackUrl()
    {
        return $this->getUrl('*/*/');
    }

    public function getSaveUrl()
    {
        return $this->getUrl('*/*/save');
    }

    public function getDeleteUrl()
    {
        $ruleId = $this->getRuleId();
        return $ruleId ? $this->getUrl('*/*/delete', ['rule_id' => $ruleId]) : '';
    }

    public function getEditUrl()
    {
        return $this->getUrl('*/*/edit');
    }

    public function getRuleId()
    {
        return $this->getRequest()->getParam('rule_id');
    }

    public function getFormKey()
    {
        return $this->formKey->getFormKey();
    }

    /**
     * Get existing rule data for edit mode
     *
     * @return array|null
     */
    public function getRuleData(): ?array
    {
        $ruleId = $this->getRuleId();
        if (!$ruleId) {
            return null;
        }

        $rule = $this->ruleFactory->create();
        $this->ruleResource->load($rule, $ruleId);

        if (!$rule->getId()) {
            return null;
        }

        // Decode JSON fields
        $smartConditions = null;
        if ($rule->getData('smart_conditions')) {
            $decoded = json_decode($rule->getData('smart_conditions'), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $smartConditions = $decoded;
            }
        }

        $badgeStyle = null;
        if ($rule->getData('badge_style')) {
            $decoded = json_decode($rule->getData('badge_style'), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $badgeStyle = $decoded;
            }
        }

        $imageSettings = null;
        if ($rule->getData('image_settings')) {
            $decoded = json_decode($rule->getData('image_settings'), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $imageSettings = $decoded;
            }
        }

        // Parse schedule dates
        $scheduleFrom = $rule->getData('schedule_from');
        $scheduleTo = $rule->getData('schedule_to');

        // Convert MySQL datetime to HTML datetime-local format (YYYY-MM-DDTHH:MM)
        if ($scheduleFrom) {
            $scheduleFrom = str_replace(' ', 'T', substr($scheduleFrom, 0, 16));
        }
        if ($scheduleTo) {
            $scheduleTo = str_replace(' ', 'T', substr($scheduleTo, 0, 16));
        }

        // Get full badge image URL if exists
        $badgeImageUrl = '';
        if ($rule->getData('badge_image')) {
            $badgeImageUrl = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA)
                . 'smartbadge/' . $rule->getData('badge_image');
        }

        return [
            'rule_id' => $rule->getId(),
            'name' => $rule->getData('name'),
            'is_active' => (bool)$rule->getData('is_active'),
            'priority' => (int)$rule->getData('priority'),
            'badge_type' => $rule->getData('badge_type') ?: 'sale',
            'badge_text' => $rule->getData('badge_text') ?: '',
            'badge_color' => $rule->getData('badge_color') ?: '#ef4444',
            'badge_icon' => $rule->getData('badge_icon') ?: '🔥',
            'animation' => $rule->getData('animation') ?: 'pulse',
            'product_ids' => $rule->getData('product_ids') ?: '',
            'category_ids' => $rule->getData('category_ids') ?: '',
            'badge_image' => $rule->getData('badge_image') ?: '',
            'badge_image_url' => $badgeImageUrl,
            'smart_conditions' => $smartConditions,
            'badge_style' => $badgeStyle,
            'image_settings' => $imageSettings,
            'schedule_from' => $scheduleFrom ?: '',
            'schedule_to' => $scheduleTo ?: '',
            // Display Settings
            'display_on' => $rule->getData('display_on') ?: 'all',
            'position_category' => $rule->getData('position_category') ?: 'top-left',
            'position_product' => $rule->getData('position_product') ?: 'top-left',
            'position_slider' => $rule->getData('position_slider') ?: 'top-left',
            'position_all' => $rule->getData('position_all') ?: 'top-left',
            'use_same_position' => (bool)$rule->getData('use_same_position')
        ];
    }

    public function getBadgeTypes()
    {
        return [
            ['value' => 'new', 'label' => '🆕 NEW', 'color' => '#10b981', 'icon' => '✨'],
            ['value' => 'sale', 'label' => '🔥 SALE', 'color' => '#ef4444', 'icon' => '🔥'],
            ['value' => 'hot', 'label' => '🔥 HOT', 'color' => '#ec4899', 'icon' => '🔥'],
            ['value' => 'limited', 'label' => '⏰ LIMITED', 'color' => '#f59e0b', 'icon' => '⚡'],
            ['value' => 'bestseller', 'label' => '⭐ BESTSELLER', 'color' => '#8b5cf6', 'icon' => '⭐'],
            ['value' => 'trending', 'label' => '📈 TRENDING', 'color' => '#06b6d4', 'icon' => '📈'],
            ['value' => 'exclusive', 'label' => '💎 EXCLUSIVE', 'color' => '#7c3aed', 'icon' => '💎'],
            ['value' => 'featured', 'label' => '✨ FEATURED', 'color' => '#14b8a6', 'icon' => '✨'],
        ];
    }

    public function getAnimations()
    {
        return [
            ['value' => 'pulse', 'label' => 'Pulse', 'preview' => 'pulse'],
            ['value' => 'bounce', 'label' => 'Bounce', 'preview' => 'bounce'],
            ['value' => 'shake', 'label' => 'Shake', 'preview' => 'shake'],
            ['value' => 'glow', 'label' => 'Glow', 'preview' => 'glow'],
            ['value' => 'rotate', 'label' => 'Rotate', 'preview' => 'rotate'],
            ['value' => 'flip', 'label' => 'Flip', 'preview' => 'flip'],
            ['value' => 'swing', 'label' => 'Swing', 'preview' => 'swing'],
            ['value' => 'tada', 'label' => 'Tada', 'preview' => 'tada'],
            ['value' => 'jello', 'label' => 'Jello', 'preview' => 'jello'],
            ['value' => 'heartbeat', 'label' => 'Heartbeat', 'preview' => 'heartbeat'],
            ['value' => 'fadeIn', 'label' => 'Fade In', 'preview' => 'fadeIn'],
            ['value' => 'fadeOut', 'label' => 'Fade Out', 'preview' => 'fadeOut'],
            ['value' => 'slideInLeft', 'label' => 'Slide In Left', 'preview' => 'slideInLeft'],
            ['value' => 'slideInRight', 'label' => 'Slide In Right', 'preview' => 'slideInRight'],
            ['value' => 'slideInUp', 'label' => 'Slide In Up', 'preview' => 'slideInUp'],
            ['value' => 'slideInDown', 'label' => 'Slide In Down', 'preview' => 'slideInDown'],
            ['value' => 'slideOutLeft', 'label' => 'Slide Out Left', 'preview' => 'slideOutLeft'],
            ['value' => 'slideOutRight', 'label' => 'Slide Out Right', 'preview' => 'slideOutRight'],
            ['value' => 'slideOutUp', 'label' => 'Slide Out Up', 'preview' => 'slideOutUp'],
            ['value' => 'slideOutDown', 'label' => 'Slide Out Down', 'preview' => 'slideOutDown'],
            ['value' => 'zoomIn', 'label' => 'Zoom In', 'preview' => 'zoomIn'],
            ['value' => 'zoomOut', 'label' => 'Zoom Out', 'preview' => 'zoomOut'],
            ['value' => 'flash', 'label' => 'Flash', 'preview' => 'flash'],
            ['value' => 'rubberBand', 'label' => 'Rubber Band', 'preview' => 'rubberBand'],
            ['value' => 'wobble', 'label' => 'Wobble', 'preview' => 'wobble'],
            ['value' => 'headShake', 'label' => 'Head Shake', 'preview' => 'headShake'],
            ['value' => 'swing2', 'label' => 'Swing 2', 'preview' => 'swing2'],
            ['value' => 'rollIn', 'label' => 'Roll In', 'preview' => 'rollIn'],
            ['value' => 'rollOut', 'label' => 'Roll Out', 'preview' => 'rollOut'],
            ['value' => 'lightSpeedIn', 'label' => 'Light Speed In', 'preview' => 'lightSpeedIn'],
        ];
    }

    /**
     * Get category tree as hierarchical array
     *
     * @return array
     */
    public function getCategoryTree()
    {
        $collection = $this->categoryCollectionFactory->create();
        $collection->addAttributeToSelect(['name', 'is_active', 'level', 'path'])
            ->addAttributeToFilter('is_active', 1)
            ->addFieldToFilter('level', ['gt' => 0]) // Exclude root catalog
            ->setOrder('path', 'ASC');

        $categoriesById = [];
        $tree = [];

        foreach ($collection as $category) {
            $categoryData = [
                'id' => (int)$category->getId(),
                'name' => $category->getName(),
                'level' => (int)$category->getLevel(),
                'path' => $category->getPath(),
                'parent_id' => (int)$category->getParentId(),
                'children' => []
            ];
            $categoriesById[$category->getId()] = $categoryData;
        }

        // Build tree structure
        foreach ($categoriesById as $id => $category) {
            if (isset($categoriesById[$category['parent_id']])) {
                $categoriesById[$category['parent_id']]['children'][] = &$categoriesById[$id];
            } else {
                // Top level category (Default Category)
                $tree[] = &$categoriesById[$id];
            }
        }

        return $tree;
    }

    /**
     * Get category tree as JSON for JavaScript
     *
     * @return string
     */
    public function getCategoryTreeJson()
    {
        return json_encode($this->getCategoryTree(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }

    /**
     * Get existing rule data as properly encoded JSON for JavaScript
     *
     * @return string
     */
    public function getRuleDataJson(): string
    {
        $ruleData = $this->getRuleData();
        if (!$ruleData) {
            return 'null';
        }

        // Must use JSON_UNESCAPED_SLASHES and NO escaping for HTML
        // The JSON will be inserted directly into JavaScript, not HTML attribute
        return json_encode($ruleData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Get all available product attributes
     *
     * @return array
     */
    public function getProductAttributes()
    {
        $collection = $this->attributeCollectionFactory->create();
        $collection->addFieldToFilter('is_visible', 1);
        $collection->setOrder('frontend_label', 'ASC');

        $attributes = [];
        foreach ($collection as $attribute) {
            $label = $attribute->getFrontendLabel();
            $code = $attribute->getAttributeCode();

            // Skip attributes without labels and system attributes
            if (!$label || in_array($code, ['entity_id', 'attribute_set_id', 'type_id'])) {
                continue;
            }

            $attributes[] = [
                'code' => $code,
                'label' => $label
            ];
        }

        // Sort by label
        usort($attributes, function($a, $b) {
            return strcmp($a['label'], $b['label']);
        });

        return $attributes;
    }
}
