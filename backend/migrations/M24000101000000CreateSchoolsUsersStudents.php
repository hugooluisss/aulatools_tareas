<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

final class M24000101000000CreateSchoolsUsersStudents implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $builder): void
    {
        $builder->execute(<<<'SQL'
            CREATE TABLE schools (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                name VARCHAR(255) NOT NULL,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);

        $builder->execute(<<<'SQL'
            CREATE TABLE users (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                school_id BIGINT UNSIGNED NOT NULL,
                role ENUM('admin', 'teacher', 'student') NOT NULL,
                email VARCHAR(255) NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                first_name VARCHAR(120) NOT NULL,
                last_name VARCHAR(120) NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uq_users_email (email),
                KEY ix_users_school_role (school_id, role),
                CONSTRAINT fk_users_school
                    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);

        $builder->execute(<<<'SQL'
            CREATE TABLE students (
                user_id BIGINT UNSIGNED NOT NULL,
                enrollment_number VARCHAR(100) NOT NULL,
                birth_date DATE NOT NULL,
                status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
                PRIMARY KEY (user_id),
                CONSTRAINT fk_students_user
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);
    }

    public function down(MigrationBuilder $builder): void
    {
        $builder->dropTable('students');
        $builder->dropTable('users');
        $builder->dropTable('schools');
    }
}
