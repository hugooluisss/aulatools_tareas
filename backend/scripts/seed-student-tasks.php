<?php

declare(strict_types=1);

use App\Auth\CurrentUser;
use App\Shared\TransactionRunner;
use App\Tasks\Repository\TaskRepository;
use App\Tasks\Service\TaskService;

require '/app/vendor/autoload.php';

$connection = (require '/app/config/db.php')();
$connection->open();
$student = $connection->createCommand(<<<'SQL'
    SELECT u.id, u.school_id, c.id AS cycle_id
    FROM users u
    INNER JOIN enrollments e ON e.student_id = u.id
    INNER JOIN academic_cycles c ON c.id = e.cycle_id
    INNER JOIN `groups` g ON g.id = e.group_id AND g.cycle_id = c.id
    WHERE u.id = 60 AND u.email = 'alumno4@example.com' AND u.role = 'student'
      AND c.name IN ('2026-2027', 'Ciclo 2026-2027') AND g.name = 'Grupo 1A'
    LIMIT 1
    SQL)->queryOne();
if ($student === false) {
    throw new RuntimeException('Carlos Hernandez enrollment in Grupo 1A / 2026-2027 not found.');
}

$subjects = $connection->createCommand(<<<'SQL'
    SELECT s.id, s.code, s.name
    FROM enrollment_subject_bindings b
    INNER JOIN enrollments e ON e.id = b.enrollment_id
    INNER JOIN subjects s ON s.id = b.subject_id
    WHERE e.student_id = :student_id AND e.cycle_id = :cycle_id
      AND e.group_id = (SELECT id FROM `groups` WHERE cycle_id = :cycle_id AND name = 'Grupo 1A' LIMIT 1)
      AND s.status = 'active'
    ORDER BY s.code, s.name
    LIMIT 4
    SQL, [':student_id' => (int) $student['id'], ':cycle_id' => (int) $student['cycle_id']])->queryAll();
if (count($subjects) !== 4) {
    throw new RuntimeException('Carlos must be enrolled in at least four active subjects.');
}
$adminId = (int) $connection->createCommand(
    "SELECT id FROM users WHERE school_id = :school_id AND role = 'admin' ORDER BY id LIMIT 1",
    [':school_id' => (int) $student['school_id']],
)->queryScalar();
if ($adminId < 1) {
    throw new RuntimeException('School admin not found.');
}

$repository = new TaskRepository($connection);
$service = new TaskService($repository, new TransactionRunner($connection));
$admin = new CurrentUser($adminId, 'admin', (int) $student['school_id']);
$tasks = [
    ['Prueba - Comprensión lectora Carlos', -7, 'pending'],
    ['Prueba - Investigación científica Carlos', 3, 'delivered'],
    ['Prueba - Problemas matemáticos Carlos', 10, 'graded'],
    ['Prueba - Historia local Carlos', 17, 'pending'],
];
$now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
foreach ($tasks as $index => [$title, $offset, $status]) {
    $subject = $subjects[$index];
    $existing = $connection->createCommand(
        'SELECT id FROM tasks WHERE subject_id = :subject_id AND cycle_id = :cycle_id AND name = :name LIMIT 1',
        [':subject_id' => (int) $subject['id'], ':cycle_id' => (int) $student['cycle_id'], ':name' => $title],
    )->queryScalar();
    if ($existing !== false && $existing !== null) {
        echo sprintf("Omitida (ya existe): %s | %s\n", $title, $subject['name']);
        continue;
    }
    $dueAt = $now->modify(($offset >= 0 ? '+' : '') . $offset . ' days')->format('Y-m-d');
    $task = $service->create($admin, (int) $subject['id'], [
        'cycle_id' => (int) $student['cycle_id'],
        'name' => $title,
        'description' => 'Tarea de prueba para Carlos Hernandez.',
        'due_at' => $dueAt,
    ]);
    $deliveries = $connection->createCommand(
        'SELECT id, student_id FROM task_deliveries WHERE task_id = :task_id ORDER BY id',
        [':task_id' => (int) $task['id']],
    )->queryAll();
    foreach ($deliveries as $delivery) {
        if ((int) $delivery['student_id'] !== (int) $student['id']) {
            continue;
        }
        $deliveryId = (int) $delivery['id'];
        if ($status === 'delivered' || $status === 'graded') {
            $service->markDelivered($admin, $deliveryId);
        }
        if ($status === 'graded') {
            $service->grade($admin, $deliveryId, ['grade' => 88]);
        }
    }
    echo sprintf("%s | %s | %s | %s\n", $title, $subject['name'], $dueAt, $status);
}
