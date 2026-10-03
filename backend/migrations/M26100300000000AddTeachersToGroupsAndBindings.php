<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

final class M26100300000000AddTeachersToGroupsAndBindings implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $builder): void
    {
        $builder->execute('ALTER TABLE `groups` ADD teacher_id BIGINT UNSIGNED NULL AFTER name, ADD KEY ix_groups_teacher (teacher_id), ADD CONSTRAINT fk_groups_teacher FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE SET NULL');
        $builder->execute('ALTER TABLE enrollment_subject_bindings ADD teacher_id BIGINT UNSIGNED NULL AFTER subject_id, ADD KEY ix_enrollment_subject_bindings_teacher (teacher_id), ADD CONSTRAINT fk_enrollment_subject_bindings_teacher FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE SET NULL');
        $builder->execute(<<<'SQL'
            UPDATE enrollment_subject_bindings bindings
            INNER JOIN subjects ON subjects.id = bindings.subject_id
            SET bindings.teacher_id = subjects.teacher_id
            SQL);
    }

    public function down(MigrationBuilder $builder): void
    {
        $builder->execute('ALTER TABLE enrollment_subject_bindings DROP FOREIGN KEY fk_enrollment_subject_bindings_teacher, DROP INDEX ix_enrollment_subject_bindings_teacher, DROP COLUMN teacher_id');
        $builder->execute('ALTER TABLE `groups` DROP FOREIGN KEY fk_groups_teacher, DROP INDEX ix_groups_teacher, DROP COLUMN teacher_id');
    }
}
