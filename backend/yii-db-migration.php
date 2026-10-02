<?php

declare(strict_types=1);

return [
    'db' => (require __DIR__ . '/config/db.php')(),
    'newMigrationNamespace' => '',
    'sourceNamespaces' => [],
    'newMigrationPath' => __DIR__ . '/migrations',
    'sourcePaths' => [__DIR__ . '/migrations'],
    'container' => null,
    'historyTable' => '{{%migration}}',
    'migrationNameLimit' => 180,
    'useTablePrefix' => true,
    'maxSqlOutputLength' => null,
];
