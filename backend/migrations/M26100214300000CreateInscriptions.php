<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

final class M26100214300000CreateInscriptions implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $builder): void
    {
        $builder->execute(<<<'SQL'
            CREATE TABLE inscriptions (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                student_id BIGINT UNSIGNED NOT NULL,
                cycle_id BIGINT UNSIGNED NOT NULL,
                group_id BIGINT UNSIGNED NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_inscriptions_student_cycle (student_id, cycle_id),
                KEY ix_inscriptions_cycle (cycle_id),
                KEY ix_inscriptions_group (group_id),
                CONSTRAINT fk_inscriptions_student
                    FOREIGN KEY (student_id) REFERENCES students(user_id) ON DELETE CASCADE,
                CONSTRAINT fk_inscriptions_cycle
                    FOREIGN KEY (cycle_id) REFERENCES academic_cycles(id) ON DELETE CASCADE,
                CONSTRAINT fk_inscriptions_group
                    FOREIGN KEY (group_id) REFERENCES `groups`(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);
        $builder->execute('UPDATE students SET enrollment_number = CAST(user_id AS CHAR)');
    }

    public function down(MigrationBuilder $builder): void
    {
        $builder->dropTable('inscriptions');
    }
}
