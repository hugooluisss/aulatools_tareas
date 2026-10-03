<?php

declare(strict_types=1);

use App\Auth\CurrentUser;
use App\Shared\TransactionRunner;
use App\Tasks\Repository\TaskRepository;
use App\Tasks\Service\TaskService;
use Yiisoft\Db\Connection\ConnectionInterface;

require '/app/vendor/autoload.php';

$connection = (require '/app/config/db.php')();
$connection->open();
$pdo = $connection->getPdo();

$rows = $connection->createCommand(<<<'SQL'
    SELECT s.id AS subject_id, s.code, s.name AS subject_name,
           c.id AS cycle_id, g.id AS group_id, g.name AS group_name,
           COUNT(DISTINCT e.student_id) AS student_count
    FROM subjects s
    INNER JOIN academic_cycles c ON c.school_id = s.school_id AND c.status = 'active'
    INNER JOIN enrollment_subject_bindings b ON b.subject_id = s.id
    INNER JOIN enrollments e ON e.id = b.enrollment_id AND e.cycle_id = c.id
    INNER JOIN `groups` g ON g.id = e.group_id AND g.cycle_id = c.id
    INNER JOIN users u ON u.id = e.student_id AND u.role = 'student' AND u.school_id = s.school_id
    INNER JOIN students st ON st.user_id = u.id AND st.status = 'active'
    WHERE s.status = 'active'
    GROUP BY s.id, s.code, s.name, c.id, g.id, g.name
    HAVING COUNT(DISTINCT e.student_id) > 0
    ORDER BY student_count DESC, s.code, g.name
    SQL)->queryAll();

if ($rows === []) {
    throw new RuntimeException('No active subject/group combinations with active students found.');
}

$schoolId = (int) $connection->createCommand(<<<'SQL'
    SELECT s.school_id
    FROM subjects s
    INNER JOIN academic_cycles c ON c.school_id = s.school_id AND c.status = 'active'
    INNER JOIN enrollment_subject_bindings b ON b.subject_id = s.id
    INNER JOIN enrollments e ON e.id = b.enrollment_id AND e.cycle_id = c.id
    INNER JOIN users u ON u.id = e.student_id AND u.role = 'student'
    INNER JOIN students st ON st.user_id = u.id AND st.status = 'active'
    WHERE s.status = 'active'
    LIMIT 1
    SQL)->queryScalar();
$adminId = (int) $connection->createCommand(
    "SELECT id FROM users WHERE school_id = :school_id AND role = 'admin' ORDER BY id LIMIT 1",
    [':school_id' => $schoolId],
)->queryScalar();
if ($schoolId < 1 || $adminId < 1) {
    throw new RuntimeException('An admin and eligible active subject/group data are required.');
}

$repository = new TaskRepository($connection);
$service = new TaskService($repository, new TransactionRunner($connection));
$admin = new CurrentUser($adminId, 'admin', $schoolId);

$titles = [
    'Prueba - Lectura y comprensión',
    'Prueba - Proyecto de ciencias',
    'Prueba - Línea del tiempo',
    'Prueba - Vocabulario semanal',
    'Prueba - Ejercicios de álgebra',
    'Prueba - Mapa regional',
    'Prueba - Actividad física',
    'Prueba - Repaso integrador A',
    'Prueba - Repaso integrador B',
    'Prueba - Repaso integrador C',
];
$dueOffsets = [-21, -12, -5, -1, 2, 7, 14, 21, 35, 49];
$created = [];

for ($index = 0; $index < count($titles); $index++) {
    $row = $rows[$index % count($rows)];
    $existing = $connection->createCommand(
        'SELECT id FROM tasks WHERE subject_id = :subject_id AND cycle_id = :cycle_id AND name = :name LIMIT 1',
        [':subject_id' => (int) $row['subject_id'], ':cycle_id' => (int) $row['cycle_id'], ':name' => $titles[$index]],
    )->queryScalar();
    if ($existing !== false && $existing !== null) {
        continue;
    }

    $dueAt = (new DateTimeImmutable('now', new DateTimeZone('UTC')))
        ->modify(($dueOffsets[$index] >= 0 ? '+' : '') . $dueOffsets[$index] . ' days')
        ->format('Y-m-d');
    $task = $service->create($admin, (int) $row['subject_id'], [
        'cycle_id' => (int) $row['cycle_id'],
        'name' => $titles[$index],
        'description' => 'Tarea de prueba para validar el overview administrativo.',
        'due_at' => $dueAt,
    ]);
    $taskId = (int) $task['id'];
    $deliveries = $connection->createCommand(
        'SELECT id FROM task_deliveries WHERE task_id = :task_id ORDER BY id',
        [':task_id' => $taskId],
    )->queryAll();
    if ($index === 3 || $index === 8) {
        $service->cancel($admin, $taskId);
    } else {
        foreach ($deliveries as $deliveryIndex => $delivery) {
            $deliveryId = (int) $delivery['id'];
            if ($deliveryIndex % 3 === 1) {
                $service->markDelivered($admin, $deliveryId);
                $service->grade($admin, $deliveryId, ['grade' => 80 + ($deliveryIndex % 21)]);
            } elseif ($deliveryIndex % 3 === 2) {
                $service->markDelivered($admin, $deliveryId);
            }
        }
    }
    $created[] = sprintf('%s | %s | %s | %d estudiantes', $row['code'], $row['group_name'], $titles[$index], count($deliveries));
}

foreach ($created as $line) {
    echo $line, PHP_EOL;
}
echo sprintf("Creadas: %d; ya existentes y omitidas: %d
", count($created), count($titles) - count($created));
