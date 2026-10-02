<?php

declare(strict_types=1);

namespace App\Shared;

use Throwable;
use Yiisoft\Db\Connection\ConnectionInterface;

final class TransactionRunner
{
    public function __construct(private ConnectionInterface $db)
    {
    }

    public function run(callable $callback): mixed
    {
        $transaction = $this->db->beginTransaction();
        try {
            $result = $callback();
            $transaction->commit();
            return $result;
        } catch (Throwable $exception) {
            $transaction->rollBack();
            throw $exception;
        }
    }
}
