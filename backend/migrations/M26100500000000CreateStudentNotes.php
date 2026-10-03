<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

final class M26100500000000CreateStudentNotes implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $builder): void
    {
        $builder->execute(<<<'SQL'
            CREATE TABLE student_notes (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                student_id BIGINT UNSIGNED NOT NULL,
                author_id BIGINT UNSIGNED NOT NULL,
                body VARCHAR(2000) NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY ix_student_notes_student_created (student_id, created_at),
                CONSTRAINT fk_student_notes_student
                    FOREIGN KEY (student_id) REFERENCES students(user_id) ON DELETE CASCADE,
                CONSTRAINT fk_student_notes_author
                    FOREIGN KEY (author_id) REFERENCES users(id),
                CHECK (CHAR_LENGTH(TRIM(body)) > 0)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);
    }

    public function down(MigrationBuilder $builder): void
    {
        $builder->dropTable('student_notes');
    }
}
