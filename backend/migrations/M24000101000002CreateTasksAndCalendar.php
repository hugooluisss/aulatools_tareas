<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

final class M24000101000002CreateTasksAndCalendar implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $builder): void
    {
        $builder->execute(<<<'SQL'
            CREATE TABLE tasks (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                subject_id BIGINT UNSIGNED NOT NULL,
                name VARCHAR(200) NOT NULL,
                description TEXT NOT NULL,
                due_at DATETIME NOT NULL,
                status ENUM('active', 'cancelled') NOT NULL DEFAULT 'active',
                PRIMARY KEY (id),
                KEY ix_tasks_subject_status (subject_id, status),
                CONSTRAINT fk_tasks_subject
                    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);

        $builder->execute(<<<'SQL'
            CREATE TABLE task_deliveries (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                task_id BIGINT UNSIGNED NOT NULL,
                student_id BIGINT UNSIGNED NOT NULL,
                status ENUM('pending', 'delivered', 'graded', 'cancelled') NOT NULL DEFAULT 'pending',
                delivered_at DATETIME NULL,
                grade DECIMAL(5, 2) NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uq_task_delivery (task_id, student_id),
                KEY ix_deliveries_student_status (student_id, status),
                CONSTRAINT fk_deliveries_task
                    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
                CONSTRAINT fk_deliveries_student
                    FOREIGN KEY (student_id) REFERENCES students(user_id) ON DELETE CASCADE,
                CHECK (grade IS NULL OR grade BETWEEN 0 AND 100)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);

        $builder->execute(<<<'SQL'
            CREATE TABLE task_comments (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                delivery_id BIGINT UNSIGNED NOT NULL,
                author_id BIGINT UNSIGNED NOT NULL,
                body VARCHAR(2000) NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY ix_task_comments_delivery_created (delivery_id, created_at),
                CONSTRAINT fk_task_comments_delivery
                    FOREIGN KEY (delivery_id) REFERENCES task_deliveries(id) ON DELETE CASCADE,
                CONSTRAINT fk_task_comments_author
                    FOREIGN KEY (author_id) REFERENCES users(id),
                CHECK (CHAR_LENGTH(TRIM(body)) > 0)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);

        $builder->execute(<<<'SQL'
            CREATE TABLE calendar_events (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                school_id BIGINT UNSIGNED NOT NULL,
                subject_id BIGINT UNSIGNED NULL,
                title VARCHAR(200) NOT NULL,
                description TEXT NOT NULL,
                starts_at DATETIME NOT NULL,
                ends_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                KEY ix_calendar_events_school_start (school_id, starts_at),
                KEY ix_calendar_events_subject_start (subject_id, starts_at),
                CONSTRAINT fk_calendar_events_school
                    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
                CONSTRAINT fk_calendar_events_subject
                    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
                CHECK (ends_at >= starts_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);

        $builder->execute(<<<'SQL'
            CREATE TABLE announcements (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                school_id BIGINT UNSIGNED NOT NULL,
                title VARCHAR(200) NOT NULL,
                body TEXT NOT NULL,
                starts_on DATE NOT NULL,
                ends_on DATE NOT NULL,
                PRIMARY KEY (id),
                KEY ix_announcements_school_period (school_id, starts_on, ends_on),
                CONSTRAINT fk_announcements_school
                    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
                CHECK (ends_on >= starts_on)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);
    }

    public function down(MigrationBuilder $builder): void
    {
        $builder->dropTable('announcements');
        $builder->dropTable('calendar_events');
        $builder->dropTable('task_comments');
        $builder->dropTable('task_deliveries');
        $builder->dropTable('tasks');
    }
}
