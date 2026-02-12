<?php

function current_user(): ?array
{
    if (!isset($_SESSION['user'])) {
        return null;
    }
    return $_SESSION['user'];
}

function require_admin(): void
{
    $user = current_user();
    if (!$user || ($user['role'] ?? '') !== 'admin') {
        redirect('/admin/login');
    }
}

function require_responsable(): void
{
    $user = current_user();
    if (!$user || ($user['role'] ?? '') !== 'responsable') {
        redirect('/activity');
    }
}

function login_admin(string $email, string $password): bool
{
    $stmt = db()->prepare('SELECT id, name, email, role, password_hash, active FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || (int)$user['active'] !== 1 || $user['role'] !== 'admin') {
        return false;
    }

    if (!password_verify($password, $user['password_hash'])) {
        return false;
    }

    $_SESSION['user'] = [
        'id' => (int)$user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => 'admin',
    ];

    return true;
}

function login_responsable(array $assignment, array $user): void
{
    $_SESSION['user'] = [
        'id' => (int)$user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => 'responsable',
        'assignment_id' => (int)$assignment['id'],
        'activity_date' => $assignment['activity_date'],
    ];
}

function logout(): void
{
    unset($_SESSION['user']);
}
