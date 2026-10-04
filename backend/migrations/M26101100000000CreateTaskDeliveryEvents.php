<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

final class M26101100000000CreateTaskDeliveryEvents implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $builder): void
    {
        $builder->execute(<<<'SQL'
            CREATE TABLE task_delivery_events (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                delivery_id BIGINT UNSIGNED NOT NULL,
                task_id BIGINT UNSIGNED NOT NULL,
                type VARCHAR(32) NOT NULL,
                actor_id BIGINT UNSIGNED NULL,
                actor_name VARCHAR(200) NULL,
                actor_role VARCHAR(32) NULL,
                created_at DATETIME NOT NULL,
                payload JSON NOT NULL,
                PRIMARY KEY (id),
                KEY ix_task_delivery_events_delivery_created (delivery_id, created_at, id),
                CONSTRAINT fk_task_delivery_events_delivery FOREIGN KEY (delivery_id) REFERENCES task_deliveries(id) ON DELETE CASCADE,
                CONSTRAINT fk_task_delivery_events_task FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
                CONSTRAINT fk_task_delivery_events_actor FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);
        $builder->execute(<<<'SQL'
            INSERT INTO task_delivery_events (delivery_id, task_id, type, created_at, payload)
            SELECT id, task_id, 'task_created', UTC_TIMESTAMP(), JSON_OBJECT()
            FROM task_deliveries
            SQL);
        $builder->execute(<<<'SQL'
            INSERT INTO task_delivery_events (delivery_id, task_id, type, actor_id, actor_name, actor_role, created_at, payload)
            SELECT task_comments.delivery_id, task_deliveries.task_id, 'comment_added', users.id,
                   CONCAT_WS(' ', users.first_name, users.last_name), users.role, task_comments.created_at,
                   JSON_OBJECT('comment_id', task_comments.id)
            FROM task_comments
            INNER JOIN task_deliveries ON task_deliveries.id = task_comments.delivery_id
            INNER JOIN users ON users.id = task_comments.author_id
            SQL);
        $builder->execute(<<<'SQL'
            INSERT INTO task_delivery_events (delivery_id, task_id, type, created_at, payload)
            SELECT id, task_id, 'delivered', delivered_at, JSON_OBJECT()
            FROM task_deliveries
            WHERE delivered_at IS NOT NULL
            SQL);
        $builder->execute(<<<'SQL'
            INSERT INTO task_delivery_events (delivery_id, task_id, type, created_at, payload)
            SELECT id, task_id, 'graded', UTC_TIMESTAMP(), JSON_OBJECT('grade', grade)
            FROM task_deliveries
            WHERE grade IS NOT NULL
            SQL);
    }

    public function down(MigrationBuilder $builder): void
    {
        $builder->dropTable('task_delivery_events');
    }
}
