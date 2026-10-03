<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

final class M26100800000000CreateTaskDeliveryStatuses implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $builder): void
    {
        $builder->execute(<<<'SQL'
            CREATE TABLE task_delivery_statuses (
                code VARCHAR(20) NOT NULL,
                label VARCHAR(40) NOT NULL,
                color CHAR(7) NOT NULL,
                text_color CHAR(7) NOT NULL,
                sort_order TINYINT UNSIGNED NOT NULL,
                PRIMARY KEY (code),
                UNIQUE KEY uq_task_delivery_statuses_order (sort_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);
        $builder->execute(<<<'SQL'
            INSERT INTO task_delivery_statuses (code, label, color, text_color, sort_order) VALUES
                ('pending', 'Pendiente', '#FFF3CD', '#664D03', 1),
                ('delivered', 'Entregada', '#D1E7DD', '#0F5132', 2),
                ('graded', 'Calificada', '#CFE2FF', '#084298', 3),
                ('cancelled', 'Cancelada', '#F8D7DA', '#842029', 4)
            SQL);
    }

    public function down(MigrationBuilder $builder): void
    {
        $builder->dropTable('task_delivery_statuses');
    }
}
