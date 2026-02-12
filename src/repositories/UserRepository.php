<?php

function user_find_by_email(string $email): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function user_all(): array
{
    $stmt = db()->query('SELECT * FROM users ORDER BY created_at DESC, id DESC');
    return $stmt->fetchAll();
}

function user_create(string $name, string $email, string $role, string $password): int
{
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = db()->prepare('INSERT INTO users (name, email, role, password_hash, active) VALUES (?, ?, ?, ?, 1)');
    $stmt->execute([$name, $email, $role, $hash]);
    return (int)db()->lastInsertId();
}

function user_update(int $id, string $name, string $email, string $role, ?string $password): void
{
    if ($password !== null && $password !== '') {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = db()->prepare('UPDATE users SET name = ?, email = ?, role = ?, password_hash = ? WHERE id = ?');
        $stmt->execute([$name, $email, $role, $hash, $id]);
        return;
    }

    $stmt = db()->prepare('UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?');
    $stmt->execute([$name, $email, $role, $id]);
}

function user_set_active(int $id, bool $active): void
{
    $stmt = db()->prepare('UPDATE users SET active = ? WHERE id = ?');
    $stmt->execute([$active ? 1 : 0, $id]);
}
