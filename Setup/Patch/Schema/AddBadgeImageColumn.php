<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Setup\Patch\Schema;

use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\DB\Ddl\Table;

/**
 * Add badge_image column to panth_smart_badge_rule table
 */
class AddBadgeImageColumn implements SchemaPatchInterface
{
    private $moduleDataSetup;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        $connection = $this->moduleDataSetup->getConnection();
        $tableName = $this->moduleDataSetup->getTable('panth_smart_badge_rule');

        // Check if column doesn't exist before adding
        if (!$connection->tableColumnExists($tableName, 'badge_image')) {
            $connection->addColumn(
                $tableName,
                'badge_image',
                [
                    'type' => Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'comment' => 'Badge Image Path'
                ]
            );
        }

        $this->moduleDataSetup->endSetup();

        return $this;
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }
}
