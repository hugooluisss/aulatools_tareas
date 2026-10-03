<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

final class M26100900000000MakeTaskDueDateDate implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $builder): void
    {
        $builder->execute('ALTER TABLE tasks MODIFY due_at DATE NOT NULL');
    }

    public function down(MigrationBuilder $builder): void
    {
        $builder->execute('ALTER TABLE tasks MODIFY due_at DATETIME NOT NULL');
    }
}
