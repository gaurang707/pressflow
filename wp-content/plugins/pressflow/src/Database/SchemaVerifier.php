<?php

declare(strict_types=1);

namespace PressFlow\Database;

final class SchemaVerifier
{
    /**
     * @return list<string>
     */
    public static function missingTables(array $tableNames): array
    {
        global $wpdb;

        $missingTables = [];

        foreach ($tableNames as $tableName) {
            $foundTable = $wpdb->get_var(
                $wpdb->prepare(
                    'SHOW TABLES LIKE %s',
                    $wpdb->esc_like($tableName)
                )
            );

            if ($foundTable !== $tableName) {
                $missingTables[] = $tableName;
            }
        }

        return $missingTables;
    }
}
