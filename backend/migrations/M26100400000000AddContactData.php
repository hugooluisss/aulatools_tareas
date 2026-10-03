<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

final class M26100400000000AddContactData implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $builder): void
    {
        $builder->execute('ALTER TABLE users ADD address VARCHAR(255) NULL, ADD phone VARCHAR(20) NULL, ADD photo_path VARCHAR(255) NULL');
        $builder->execute('ALTER TABLE students ADD address VARCHAR(255) NULL, ADD contact_phone VARCHAR(20) NULL, ADD guardian_name VARCHAR(180) NULL, ADD guardian_phone VARCHAR(20) NULL');
        $builder->execute('ALTER TABLE schools ADD address VARCHAR(255) NULL, ADD phone VARCHAR(20) NULL, ADD email VARCHAR(255) NULL');
    }

    public function down(MigrationBuilder $builder): void
    {
        $builder->execute('ALTER TABLE schools DROP COLUMN email, DROP COLUMN phone, DROP COLUMN address');
        $builder->execute('ALTER TABLE students DROP COLUMN guardian_phone, DROP COLUMN guardian_name, DROP COLUMN contact_phone, DROP COLUMN address');
        $builder->execute('ALTER TABLE users DROP COLUMN photo_path, DROP COLUMN phone, DROP COLUMN address');
    }
}
