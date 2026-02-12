<?php

function activity_create(int $assignment_id, string $description, ?float $total_value): int
{
    $stmt = db()->prepare('INSERT INTO activities (assignment_id, description, total_value) VALUES (?, ?, ?)');
    $stmt->execute([$assignment_id, $description, $total_value]);
    return (int)db()->lastInsertId();
}

function activity_find_by_assignment(int $assignment_id): ?array
{
    $stmt = db()->prepare('SELECT * FROM activities WHERE assignment_id = ? ORDER BY created_at DESC LIMIT 1');
    $stmt->execute([$assignment_id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function activity_get(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM activities WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function activity_list(array $filters = []): array
{
    $sql = 'SELECT a.*, asg.activity_date, u.name AS user_name '
        . 'FROM activities a '
        . 'JOIN assignments asg ON asg.id = a.assignment_id '
        . 'JOIN users u ON u.id = asg.user_id ';

    $params = [];
    $where = [];
    if (!empty($filters['date'])) {
        $where[] = 'asg.activity_date = ?';
        $params[] = $filters['date'];
    }

    if (!empty($filters['activity_id'])) {
        $where[] = 'a.id = ?';
        $params[] = $filters['activity_id'];
    }

    if ($where) {
        $sql .= 'WHERE ' . implode(' AND ', $where) . ' ';
    }

    $sql .= 'ORDER BY a.created_at DESC, a.id DESC';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}
