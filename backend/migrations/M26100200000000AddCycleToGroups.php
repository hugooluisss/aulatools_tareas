<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

final class M26100200000000AddCycleToGroups implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $builder): void
    {
        $builder->execute('ALTER TABLE `groups` ADD cycle_id BIGINT UNSIGNED NULL AFTER school_id');
        $builder->execute(<<<'SQL'
            UPDATE `groups`
            INNER JOIN (
                SELECT school_id, MIN(id) AS cycle_id
                FROM academic_cycles
                GROUP BY school_id
            ) AS cycles ON cycles.school_id = `groups`.school_id
            SET `groups`.cycle_id = cycles.cycle_id
            SQL);
        $builder->execute('ALTER TABLE `groups` MODIFY cycle_id BIGINT UNSIGNED NOT NULL');
        $builder->execute('ALTER TABLE `groups` ADD KEY ix_groups_cycle (cycle_id)');
        $builder->execute(<<<'SQL'
            ALTER TABLE `groups`
            ADD CONSTRAINT fk_groups_cycle
            FOREIGN KEY (cycle_id) REFERENCES academic_cycles(id)
            SQL);
    }

    public function down(MigrationBuilder $builder): void
    {
        $builder->execute('ALTER TABLE `groups` DROP FOREIGN KEY fk_groups_cycle');
        $builder->execute('ALTER TABLE `groups` DROP INDEX ix_groups_cycle');
        $builder->execute('ALTER TABLE `groups` DROP COLUMN cycle_id');
    }
}
