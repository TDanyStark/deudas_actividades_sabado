<?php

function assignment_all(): array
{
    $stmt = db()->query(
        'SELECT a.*, u.name AS user_name, u.email AS user_email '
        . 'FROM assignments a '
        . 'JOIN users u ON u.id = a.user_id '
        . 'ORDER BY a.created_at DESC, a.id DESC'
    );
    return $stmt->fetchAll();
}

function assignment_count(): int
{
    $stmt = db()->query('SELECT COUNT(*) AS total FROM assignments');
    $row = $stmt->fetch();
    return $row ? (int)$row['total'] : 0;
}

function assignment_paginated(int $limit, int $offset): array
{
    $sql = 'SELECT a.*, u.name AS user_name, u.email AS user_email '
        . 'FROM assignments a '
        . 'JOIN users u ON u.id = a.user_id '
        . 'ORDER BY a.created_at DESC, a.id DESC '
        . 'LIMIT :limit OFFSET :offset';

    $stmt = db()->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function assignment_create(int $user_id, string $activity_date): int
{
    $token = bin2hex(random_bytes(16));
    $expires = $activity_date . ' 23:59:59';

    $stmt = db()->prepare(
        'INSERT INTO assignments (user_id, activity_date, token, token_expires_at, active) VALUES (?, ?, ?, ?, 1)'
    );
    $stmt->execute([$user_id, $activity_date, $token, $expires]);

    return (int)db()->lastInsertId();
}

function assignment_regenerate_token(int $assignment_id): string
{
    $token = bin2hex(random_bytes(16));
    $stmt = db()->prepare('UPDATE assignments SET token = ? WHERE id = ?');
    $stmt->execute([$token, $assignment_id]);
    return $token;
}

function assignment_find_by_token(string $token): ?array
{
    $stmt = db()->prepare(
        'SELECT a.*, u.name AS user_name, u.email AS user_email, u.id AS user_id '
        . 'FROM assignments a '
        . 'JOIN users u ON u.id = a.user_id '
        . 'WHERE a.token = ? AND a.active = 1 AND u.active = 1 LIMIT 1'
    );
    $stmt->execute([$token]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function assignment_get(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM assignments WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}
