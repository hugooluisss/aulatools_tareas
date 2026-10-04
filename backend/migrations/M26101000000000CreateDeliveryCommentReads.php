<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

final class M26101000000000CreateDeliveryCommentReads implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $builder): void
    {
        $builder->execute(<<<'SQL'
            CREATE TABLE delivery_comment_reads (
                user_id BIGINT UNSIGNED NOT NULL,
                delivery_id BIGINT UNSIGNED NOT NULL,
                last_read_comment_id BIGINT UNSIGNED NULL,
                PRIMARY KEY (user_id, delivery_id),
                KEY ix_delivery_comment_reads_delivery (delivery_id),
                CONSTRAINT fk_delivery_comment_reads_user
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                CONSTRAINT fk_delivery_comment_reads_delivery
                    FOREIGN KEY (delivery_id) REFERENCES task_deliveries(id) ON DELETE CASCADE,
                CONSTRAINT fk_delivery_comment_reads_comment
                    FOREIGN KEY (last_read_comment_id) REFERENCES task_comments(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);
    }

    public function down(MigrationBuilder $builder): void
    {
        $builder->dropTable('delivery_comment_reads');
    }
}
