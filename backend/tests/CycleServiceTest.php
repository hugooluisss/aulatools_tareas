<?php

declare(strict_types=1);

namespace App\Tests;

use App\Auth\CurrentUser;
use App\Cycles\Repository\CycleRepository;
use App\Cycles\Service\CycleException;
use App\Cycles\Service\CycleService;
use App\Shared\TransactionRunner;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Transaction\TransactionInterface;

final class CycleServiceTest extends TestCase
{
    public function testInvalidDateRangeIsRejected(): void
    {
        $repository = $this->createMock(CycleRepository::class);
        $service = new CycleService($repository, new TransactionRunner($this->createMock(ConnectionInterface::class)));

        try {
            $service->create(new CurrentUser(1, 'admin', 12), [
                'name' => '2026', 'starts_on' => '2026-08-01', 'ends_on' => '2026-07-01',
            ]);
            self::fail('Expected invalid dates to be rejected.');
        } catch (CycleException $exception) {
            self::assertSame(400, $exception->status);
        }
    }

    public function testTeacherCanListCycles(): void
    {
        $repository = $this->createMock(CycleRepository::class);
        $repository->method('list')->with(12, 0, 100)->willReturn(['data' => [['id' => 5]], 'total' => 1]);
        $service = new CycleService($repository, new TransactionRunner($this->createMock(ConnectionInterface::class)));

        self::assertSame(1, $service->list(new CurrentUser(2, 'teacher', 12), 1, 100)['total']);
    }

    public function testCycleFinishRunsRepositoryUpdateInTransaction(): void
    {
        $repository = $this->createMock(CycleRepository::class);
        $repository->method('find')->willReturnOnConsecutiveCalls(
            ['id' => 5, 'school_id' => 12, 'status' => 'active'],
            ['id' => 5, 'school_id' => 12, 'status' => 'finished'],
        );
        $repository->expects(self::once())->method('finish')->with(12, 5);
        $transaction = $this->createMock(TransactionInterface::class);
        $transaction->expects(self::once())->method('commit');
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())->method('beginTransaction')->willReturn($transaction);
        $service = new CycleService($repository, new TransactionRunner($connection));

        self::assertSame('finished', $service->finish(new CurrentUser(1, 'admin', 12), 5)['status']);
    }

    public function testCycleRepositoryExposesOpenCycleCheck(): void
    {
        $repository = $this->createMock(CycleRepository::class);
        $repository->expects(self::once())->method('isOpen')->with(12, 5)->willReturn(false);

        self::assertFalse($repository->isOpen(12, 5));
    }
}
