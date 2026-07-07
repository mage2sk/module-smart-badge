<?php
declare(strict_types=1);
namespace Panth\SmartBadge\Setup\Patch\Data;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Catalog\Model\Product;

class AddCustomBadgeAttributes implements DataPatchInterface
{
    private $moduleDataSetup;
    private $eavSetupFactory;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    public function apply()
    {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $eavSetup->addAttribute(Product::ENTITY, 'badge_custom_text', [
            'type' => 'varchar',
            'label' => 'Custom Badge Text',
            'input' => 'text',
            'required' => false,
            'sort_order' => 101,
            'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
            'group' => 'Product Details',
            'note' => 'Override badge text (leave empty to use default)',
            'visible' => true,
            'user_defined' => true,
            'used_in_product_listing' => true,
        ]);

        $eavSetup->addAttribute(Product::ENTITY, 'badge_custom_color', [
            'type' => 'varchar',
            'label' => 'Custom Badge Color',
            'input' => 'text',
            'required' => false,
            'sort_order' => 102,
            'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
            'group' => 'Product Details',
            'note' => 'Custom color (hex code like #ff0000) - overrides theme colors',
            'visible' => true,
            'user_defined' => true,
            'used_in_product_listing' => true,
            'frontend_class' => 'validate-color',
        ]);

        return $this;
    }

    public static function getDependencies()
    {
        return [\Panth\SmartBadge\Setup\Patch\Data\AddBadgeAttribute::class];
    }

    public function getAliases()
    {
        return [];
    }
}
