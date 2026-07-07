<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Setup\Patch\Schema;

use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Psr\Log\LoggerInterface;

class ConvertToUtf8mb4 implements SchemaPatchInterface
{
    private $moduleDataSetup;

    private $logger;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        LoggerInterface $logger
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->logger = $logger;
    }

    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        $connection = $this->moduleDataSetup->getConnection();

        $tablesToConvert = [
            'panth_smart_badge' => [
                'name' => 255,
                'label_text' => 'TEXT',
                'css_class' => 100
            ],
            'panth_smart_badge_rule' => [
                'name' => 255,
                'badge_text' => 255,
                'badge_icon' => 255,
                'badge_image' => 255
            ]
        ];

        foreach ($tablesToConvert as $tableName => $columns) {
            $fullTableName = $this->moduleDataSetup->getTable($tableName);

            try {
                $sql = sprintf(
                    "ALTER TABLE %s CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
                    $connection->quoteIdentifier($fullTableName)
                );
                $connection->query($sql);
                $this->logger->info("Converted table {$tableName} to utf8mb4");
            } catch (\Exception $e) {
                $this->logger->info("Table {$tableName} conversion skipped: " . $e->getMessage());
            }

            foreach ($columns as $columnName => $length) {
                try {
                    if (!$connection->tableColumnExists($fullTableName, $columnName)) {
                        $this->logger->info("Column {$columnName} does not exist in {$tableName}, skipping");
                        continue;
                    }

                    if ($length === 'TEXT') {
                        $sql = sprintf(
                            "ALTER TABLE %s MODIFY COLUMN %s TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
                            $connection->quoteIdentifier($fullTableName),
                            $connection->quoteIdentifier($columnName)
                        );
                    } else {
                        $sql = sprintf(
                            "ALTER TABLE %s MODIFY COLUMN %s VARCHAR(%d) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
                            $connection->quoteIdentifier($fullTableName),
                            $connection->quoteIdentifier($columnName),
                            $length
                        );
                    }

                    $connection->query($sql);
                    $this->logger->info("Converted column {$tableName}.{$columnName} to utf8mb4");
                } catch (\Exception $e) {
                    $this->logger->info("Column {$tableName}.{$columnName} conversion skipped: " . $e->getMessage());
                }
            }
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
