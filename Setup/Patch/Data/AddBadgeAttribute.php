<?php
declare(strict_types=1);
namespace Panth\SmartBadge\Setup\Patch\Data;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Catalog\Model\Product;

class AddBadgeAttribute implements DataPatchInterface
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
        $eavSetup->addAttribute(Product::ENTITY, 'product_badge', [
            'type' => 'varchar',
            'label' => 'Product Badge',
            'input' => 'select',
            'source' => 'Panth\SmartBadge\Model\Config\Source\BadgeOptions',
            'required' => false,
            'sort_order' => 100,
            'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
            'group' => 'Product Details',
            'visible' => true,
            'user_defined' => true,
            'used_in_product_listing' => true,
        ]);
        return $this;
    }

    public static function getDependencies() { return []; }
    public function getAliases() { return []; }
}
