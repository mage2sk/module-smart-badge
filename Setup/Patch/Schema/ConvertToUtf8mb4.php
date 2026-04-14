<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Setup\Patch\Schema;

use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Psr\Log\LoggerInterface;

/**
 * Convert database tables and columns to utf8mb4 to support emojis
 *
 * This patch automatically fixes charset/collation issues when the module
 * is installed in another project. It's idempotent and safe to run multiple times.
 */
class ConvertToUtf8mb4 implements SchemaPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param LoggerInterface $logger
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        LoggerInterface $logger
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->logger = $logger;
    }

    /**
     * Apply the patch - convert tables and columns to utf8mb4
     *
     * @return $this
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        $connection = $this->moduleDataSetup->getConnection();

        // Define tables and their text columns that need utf8mb4 support
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

            // First, convert the table itself to utf8mb4
            try {
                $sql = sprintf(
                    "ALTER TABLE %s CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
                    $connection->quoteIdentifier($fullTableName)
                );
                $connection->query($sql);
                $this->logger->info("Converted table {$tableName} to utf8mb4");
            } catch (\Exception $e) {
                // Table might already be utf8mb4, log and continue
                $this->logger->info("Table {$tableName} conversion skipped: " . $e->getMessage());
            }

            // Then, explicitly convert each text column
            foreach ($columns as $columnName => $length) {
                try {
                    // Check if column exists before attempting conversion
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
                    // Column might already be utf8mb4, log and continue
                    $this->logger->info("Column {$tableName}.{$columnName} conversion skipped: " . $e->getMessage());
                }
            }
        }

        $this->moduleDataSetup->endSetup();

        return $this;
    }

    /**
     * Get array of patches that have to be executed prior to this
     *
     * @return string[]
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * Get aliases (previous names) for the patch
     *
     * @return string[]
     */
    public function getAliases()
    {
        return [];
    }
}
