<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

final class M26100218000000NormalizeEnrollmentSubjectBindings implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $builder): void
    {
        $builder->execute('RENAME TABLE enrollments TO enrollments_legacy, inscriptions TO enrollments');
        $builder->execute('ALTER TABLE enrollments_legacy DROP FOREIGN KEY fk_enrollments_cycle, DROP FOREIGN KEY fk_enrollments_group, DROP FOREIGN KEY fk_enrollments_student, DROP FOREIGN KEY fk_enrollments_subject');
        $builder->execute(<<<'SQL'
            ALTER TABLE enrollments
            ADD CONSTRAINT fk_enrollments_cycle FOREIGN KEY (cycle_id) REFERENCES academic_cycles(id) ON DELETE CASCADE,
            ADD CONSTRAINT fk_enrollments_group FOREIGN KEY (group_id) REFERENCES `groups`(id) ON DELETE CASCADE,
            ADD CONSTRAINT fk_enrollments_student FOREIGN KEY (student_id) REFERENCES students(user_id) ON DELETE CASCADE
            SQL);

        $builder->execute(<<<'SQL'
            CREATE TABLE enrollment_subject_bindings (
                enrollment_id BIGINT UNSIGNED NOT NULL,
                subject_id BIGINT UNSIGNED NOT NULL,
                PRIMARY KEY (enrollment_id, subject_id),
                KEY ix_enrollment_subject_bindings_subject (subject_id),
                CONSTRAINT fk_enrollment_subject_bindings_enrollment
                    FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE,
                CONSTRAINT fk_enrollment_subject_bindings_subject
                    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);
        $builder->execute(<<<'SQL'
            INSERT INTO enrollment_subject_bindings (enrollment_id, subject_id)
            SELECT e.id, l.subject_id
            FROM enrollments_legacy l
            INNER JOIN enrollments e ON e.student_id = l.student_id AND e.cycle_id = l.cycle_id
            SQL);
        $builder->execute('DROP TABLE enrollments_legacy');

        $builder->execute('ALTER TABLE subjects RENAME INDEX uq_subjects_school_clave TO uq_subjects_school_code');
        $builder->execute('ALTER TABLE subjects RENAME COLUMN clave TO code');
        $builder->execute('ALTER TABLE study_plans RENAME INDEX uq_study_plans_school_clave TO uq_study_plans_school_code');
        $builder->execute('ALTER TABLE study_plans RENAME COLUMN clave TO code');
    }

    public function down(MigrationBuilder $builder): void
    {
        $builder->execute(<<<'SQL'
            CREATE TABLE enrollments_legacy (
                student_id BIGINT UNSIGNED NOT NULL,
                subject_id BIGINT UNSIGNED NOT NULL,
                cycle_id BIGINT UNSIGNED NOT NULL,
                source_group_id BIGINT UNSIGNED DEFAULT NULL,
                PRIMARY KEY (student_id, subject_id, cycle_id),
                KEY ix_enrollments_subject (subject_id),
                KEY ix_enrollments_group (source_group_id),
                KEY ix_enrollments_cycle (cycle_id),
                CONSTRAINT fk_enrollments_legacy_cycle FOREIGN KEY (cycle_id) REFERENCES academic_cycles(id) ON DELETE CASCADE,
                CONSTRAINT fk_enrollments_legacy_group FOREIGN KEY (source_group_id) REFERENCES `groups`(id) ON DELETE SET NULL,
                CONSTRAINT fk_enrollments_legacy_student FOREIGN KEY (student_id) REFERENCES students(user_id) ON DELETE CASCADE,
                CONSTRAINT fk_enrollments_legacy_subject FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);
        $builder->execute(<<<'SQL'
            INSERT INTO enrollments_legacy (student_id, subject_id, cycle_id)
            SELECT e.student_id, b.subject_id, e.cycle_id
            FROM enrollment_subject_bindings b
            INNER JOIN enrollments e ON e.id = b.enrollment_id
            SQL);
        $builder->execute('DROP TABLE enrollment_subject_bindings');

        $builder->execute('ALTER TABLE enrollments DROP FOREIGN KEY fk_enrollments_cycle, DROP FOREIGN KEY fk_enrollments_group, DROP FOREIGN KEY fk_enrollments_student');
        $builder->execute(<<<'SQL'
            ALTER TABLE enrollments
            ADD CONSTRAINT fk_inscriptions_cycle FOREIGN KEY (cycle_id) REFERENCES academic_cycles(id) ON DELETE CASCADE,
            ADD CONSTRAINT fk_inscriptions_group FOREIGN KEY (group_id) REFERENCES `groups`(id) ON DELETE CASCADE,
            ADD CONSTRAINT fk_inscriptions_student FOREIGN KEY (student_id) REFERENCES students(user_id) ON DELETE CASCADE
            SQL);
        $builder->execute('RENAME TABLE enrollments TO inscriptions, enrollments_legacy TO enrollments');

        $builder->execute('ALTER TABLE subjects RENAME INDEX uq_subjects_school_code TO uq_subjects_school_clave');
        $builder->execute('ALTER TABLE subjects RENAME COLUMN code TO clave');
        $builder->execute('ALTER TABLE study_plans RENAME INDEX uq_study_plans_school_code TO uq_study_plans_school_clave');
        $builder->execute('ALTER TABLE study_plans RENAME COLUMN code TO clave');
    }
}
