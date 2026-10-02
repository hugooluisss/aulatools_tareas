<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

final class M24000101000001CreateAcademicCyclesSubjectsGroupsEnrollments implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $builder): void
    {
        $builder->execute(<<<'SQL'
            CREATE TABLE academic_cycles (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                school_id BIGINT UNSIGNED NOT NULL,
                name VARCHAR(150) NOT NULL,
                starts_on DATE NOT NULL,
                ends_on DATE NOT NULL,
                status ENUM('active', 'finished') NOT NULL DEFAULT 'active',
                PRIMARY KEY (id),
                KEY ix_academic_cycles_school (school_id),
                CONSTRAINT fk_academic_cycles_school
                    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
                CHECK (ends_on >= starts_on)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);

        $builder->execute(<<<'SQL'
            CREATE TABLE subjects (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                cycle_id BIGINT UNSIGNED NOT NULL,
                teacher_id BIGINT UNSIGNED NOT NULL,
                name VARCHAR(180) NOT NULL,
                status ENUM('in_progress', 'finished') NOT NULL DEFAULT 'in_progress',
                PRIMARY KEY (id),
                KEY ix_subjects_cycle (cycle_id),
                KEY ix_subjects_teacher (teacher_id),
                CONSTRAINT fk_subjects_cycle
                    FOREIGN KEY (cycle_id) REFERENCES academic_cycles(id) ON DELETE CASCADE,
                CONSTRAINT fk_subjects_teacher
                    FOREIGN KEY (teacher_id) REFERENCES users(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);

        $builder->execute(<<<'SQL'
            CREATE TABLE `groups` (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                school_id BIGINT UNSIGNED NOT NULL,
                name VARCHAR(120) NOT NULL,
                PRIMARY KEY (id),
                KEY ix_groups_school (school_id),
                CONSTRAINT fk_groups_school
                    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);

        $builder->execute(<<<'SQL'
            CREATE TABLE group_subjects (
                group_id BIGINT UNSIGNED NOT NULL,
                subject_id BIGINT UNSIGNED NOT NULL,
                PRIMARY KEY (group_id, subject_id),
                KEY ix_group_subjects_subject (subject_id),
                CONSTRAINT fk_group_subjects_group
                    FOREIGN KEY (group_id) REFERENCES `groups`(id) ON DELETE CASCADE,
                CONSTRAINT fk_group_subjects_subject
                    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);

        $builder->execute(<<<'SQL'
            CREATE TABLE enrollments (
                student_id BIGINT UNSIGNED NOT NULL,
                subject_id BIGINT UNSIGNED NOT NULL,
                source_group_id BIGINT UNSIGNED NULL,
                PRIMARY KEY (student_id, subject_id),
                KEY ix_enrollments_subject (subject_id),
                KEY ix_enrollments_group (source_group_id),
                CONSTRAINT fk_enrollments_student
                    FOREIGN KEY (student_id) REFERENCES students(user_id) ON DELETE CASCADE,
                CONSTRAINT fk_enrollments_subject
                    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
                CONSTRAINT fk_enrollments_group
                    FOREIGN KEY (source_group_id) REFERENCES `groups`(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);
    }

    public function down(MigrationBuilder $builder): void
    {
        $builder->dropTable('enrollments');
        $builder->dropTable('group_subjects');
        $builder->execute('DROP TABLE `groups`');
        $builder->dropTable('subjects');
        $builder->dropTable('academic_cycles');
    }
}
