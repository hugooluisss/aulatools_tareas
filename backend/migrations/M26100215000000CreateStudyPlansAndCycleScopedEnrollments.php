<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

/**
 * Development-only reset: dependent subject data is deleted and down() restores schema only.
 */
final class M26100215000000CreateStudyPlansAndCycleScopedEnrollments implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $builder): void
    {
        $builder->execute('DELETE FROM task_comments');
        $builder->execute('DELETE FROM task_deliveries');
        $builder->execute('DELETE FROM tasks');
        $builder->execute('DELETE FROM calendar_events WHERE subject_id IS NOT NULL');
        $builder->execute('DELETE FROM enrollments');
        $builder->execute('DELETE FROM group_subjects');
        $builder->execute('DELETE FROM subjects');

        $builder->execute(<<<'SQL'
            CREATE TABLE study_plans (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                school_id BIGINT UNSIGNED NOT NULL,
                clave VARCHAR(40) NOT NULL,
                name VARCHAR(150) NOT NULL,
                status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
                PRIMARY KEY (id),
                UNIQUE KEY uq_study_plans_school_clave (school_id, clave),
                KEY ix_study_plans_school (school_id),
                CONSTRAINT fk_study_plans_school
                    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);

        $builder->execute('ALTER TABLE subjects DROP FOREIGN KEY fk_subjects_cycle');
        $builder->execute('ALTER TABLE subjects DROP INDEX ix_subjects_cycle');
        $builder->execute('ALTER TABLE subjects DROP COLUMN cycle_id');
        $builder->execute('ALTER TABLE subjects ADD school_id BIGINT UNSIGNED NOT NULL AFTER id');
        $builder->execute('ALTER TABLE subjects ADD clave VARCHAR(40) NOT NULL AFTER name');
        $builder->execute('ALTER TABLE subjects ADD plan_id BIGINT UNSIGNED NULL AFTER school_id');
        $builder->execute("ALTER TABLE subjects MODIFY status ENUM('active', 'inactive') NOT NULL DEFAULT 'active'");
        $builder->execute('ALTER TABLE subjects ADD UNIQUE KEY uq_subjects_school_clave (school_id, clave)');
        $builder->execute('ALTER TABLE subjects ADD KEY ix_subjects_school (school_id)');
        $builder->execute('ALTER TABLE subjects ADD KEY ix_subjects_plan (plan_id)');
        $builder->execute(<<<'SQL'
            ALTER TABLE subjects
            ADD CONSTRAINT fk_subjects_school
                FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
            ADD CONSTRAINT fk_subjects_plan
                FOREIGN KEY (plan_id) REFERENCES study_plans(id) ON DELETE SET NULL
            SQL);

        $builder->execute('ALTER TABLE enrollments DROP FOREIGN KEY fk_enrollments_student');
        $builder->execute('ALTER TABLE enrollments DROP PRIMARY KEY');
        $builder->execute('ALTER TABLE enrollments ADD cycle_id BIGINT UNSIGNED NOT NULL AFTER subject_id');
        $builder->execute('ALTER TABLE enrollments ADD PRIMARY KEY (student_id, subject_id, cycle_id)');
        $builder->execute('ALTER TABLE enrollments ADD KEY ix_enrollments_cycle (cycle_id)');
        $builder->execute(<<<'SQL'
            ALTER TABLE enrollments
            ADD CONSTRAINT fk_enrollments_cycle
                FOREIGN KEY (cycle_id) REFERENCES academic_cycles(id) ON DELETE CASCADE,
            ADD CONSTRAINT fk_enrollments_student
                FOREIGN KEY (student_id) REFERENCES students(user_id) ON DELETE CASCADE
            SQL);

        $builder->execute('ALTER TABLE tasks ADD cycle_id BIGINT UNSIGNED NOT NULL AFTER subject_id');
        $builder->execute('ALTER TABLE tasks ADD KEY ix_tasks_cycle (cycle_id)');
        $builder->execute(<<<'SQL'
            ALTER TABLE tasks
            ADD CONSTRAINT fk_tasks_cycle
                FOREIGN KEY (cycle_id) REFERENCES academic_cycles(id) ON DELETE CASCADE
            SQL);
    }

    public function down(MigrationBuilder $builder): void
    {
        $builder->execute('ALTER TABLE tasks DROP FOREIGN KEY fk_tasks_cycle');
        $builder->execute('ALTER TABLE tasks DROP INDEX ix_tasks_cycle');
        $builder->execute('ALTER TABLE tasks DROP COLUMN cycle_id');

        $builder->execute('ALTER TABLE enrollments DROP FOREIGN KEY fk_enrollments_cycle');
        $builder->execute('ALTER TABLE enrollments DROP FOREIGN KEY fk_enrollments_student');
        $builder->execute('ALTER TABLE enrollments DROP INDEX ix_enrollments_cycle');
        $builder->execute('ALTER TABLE enrollments DROP PRIMARY KEY');
        $builder->execute('ALTER TABLE enrollments DROP COLUMN cycle_id');
        $builder->execute('ALTER TABLE enrollments ADD PRIMARY KEY (student_id, subject_id)');
        $builder->execute(<<<'SQL'
            ALTER TABLE enrollments
            ADD CONSTRAINT fk_enrollments_student
                FOREIGN KEY (student_id) REFERENCES students(user_id) ON DELETE CASCADE
            SQL);

        $builder->execute('ALTER TABLE subjects DROP FOREIGN KEY fk_subjects_plan');
        $builder->execute('ALTER TABLE subjects DROP FOREIGN KEY fk_subjects_school');
        $builder->execute('ALTER TABLE subjects DROP INDEX uq_subjects_school_clave');
        $builder->execute('ALTER TABLE subjects DROP INDEX ix_subjects_school');
        $builder->execute('ALTER TABLE subjects DROP INDEX ix_subjects_plan');
        $builder->execute('ALTER TABLE subjects DROP COLUMN plan_id');
        $builder->execute('ALTER TABLE subjects DROP COLUMN school_id');
        $builder->execute('ALTER TABLE subjects DROP COLUMN clave');
        $builder->execute("ALTER TABLE subjects MODIFY status ENUM('in_progress', 'finished') NOT NULL DEFAULT 'in_progress'");
        $builder->execute('ALTER TABLE subjects ADD cycle_id BIGINT UNSIGNED NOT NULL AFTER id');
        $builder->execute('ALTER TABLE subjects ADD KEY ix_subjects_cycle (cycle_id)');
        $builder->execute(<<<'SQL'
            ALTER TABLE subjects
            ADD CONSTRAINT fk_subjects_cycle
                FOREIGN KEY (cycle_id) REFERENCES academic_cycles(id) ON DELETE CASCADE
            SQL);

        $builder->dropTable('study_plans');
    }
}
