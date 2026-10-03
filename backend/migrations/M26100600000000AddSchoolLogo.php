<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

final class M26100600000000AddSchoolLogo implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $builder): void
    {
        $builder->execute('ALTER TABLE schools ADD logo_path VARCHAR(255) NULL');
    }

    public function down(MigrationBuilder $builder): void
    {
        $builder->execute('ALTER TABLE schools DROP COLUMN logo_path');
    }
}
