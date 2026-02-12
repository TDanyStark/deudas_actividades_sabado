<?php

function debtor_create(int $activity_id, string $name, ?int $units, float $amount, ?string $note): int
{
    $stmt = db()->prepare('INSERT INTO debtors (activity_id, debtor_name, units, amount, note) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$activity_id, $name, $units, $amount, $note]);
    return (int)db()->lastInsertId();
}

function debtor_remaining_amount(int $debtor_id): float
{
    $stmt = db()->prepare(
        'SELECT d.amount - COALESCE(SUM(p.paid_amount), 0) AS remaining '
        . 'FROM debtors d '
        . 'LEFT JOIN payments p ON p.debtor_id = d.id '
        . 'WHERE d.id = ? GROUP BY d.id'
    );
    $stmt->execute([$debtor_id]);
    $row = $stmt->fetch();
    return $row ? (float)$row['remaining'] : 0.0;
}

function payment_create(int $debtor_id, float $amount, string $role, ?int $user_id): void
{
    $stmt = db()->prepare('INSERT INTO payments (debtor_id, paid_amount, paid_by_user_id, paid_by_role) VALUES (?, ?, ?, ?)');
    $stmt->execute([$debtor_id, $amount, $user_id, $role]);
}

function debts_public_list(array $filters = []): array
{
    $sql = 'SELECT d.id AS debtor_id, d.debtor_name, d.units, d.amount, d.note, '
        . 'a.id AS activity_id, a.description, asg.activity_date, u.name AS user_name, '
        . 'COALESCE(SUM(p.paid_amount), 0) AS paid_total '
        . 'FROM debtors d '
        . 'JOIN activities a ON a.id = d.activity_id '
        . 'JOIN assignments asg ON asg.id = a.assignment_id '
        . 'JOIN users u ON u.id = asg.user_id '
        . 'LEFT JOIN payments p ON p.debtor_id = d.id ';

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

    $sql .= 'GROUP BY d.id ORDER BY asg.activity_date DESC, d.debtor_name ASC';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function debts_summary_by_name(): array
{
    $stmt = db()->query(
        'SELECT d.debtor_name, '
        . 'SUM(d.amount) AS total_amount, '
        . 'COALESCE(SUM(p.paid_amount), 0) AS paid_total '
        . 'FROM debtors d '
        . 'LEFT JOIN payments p ON p.debtor_id = d.id '
        . 'GROUP BY d.debtor_name '
        . 'ORDER BY d.debtor_name ASC'
    );
    return $stmt->fetchAll();
}

function debtors_by_activity(int $activity_id): array
{
    $stmt = db()->prepare(
        'SELECT d.*, COALESCE(SUM(p.paid_amount), 0) AS paid_total '
        . 'FROM debtors d '
        . 'LEFT JOIN payments p ON p.debtor_id = d.id '
        . 'WHERE d.activity_id = ? '
        . 'GROUP BY d.id '
        . 'ORDER BY d.debtor_name ASC'
    );
    $stmt->execute([$activity_id]);
    return $stmt->fetchAll();
}
