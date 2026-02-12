<?php

require __DIR__ . '/../../src/bootstrap.php';
require __DIR__ . '/../../src/repositories/index.php';

if (current_user() && current_user()['role'] === 'admin') {
    redirect('/admin/users');
}

$error = '';

if (is_post()) {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!login_admin($email, $password)) {
        $error = 'Credenciales invalidas.';
    } else {
        redirect('/admin/users');
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Admin Login</title>
</head>
<body>
    <h1>Admin Login</h1>
    <?php if ($error): ?>
        <p style="color:red;"><?php echo h($error); ?></p>
    <?php endif; ?>
    <form method="post">
        <?php echo csrf_field(); ?>
        <label>Email</label><br>
        <input type="email" name="email" required><br><br>
        <label>Clave</label><br>
        <input type="password" name="password" required><br><br>
        <button type="submit">Entrar</button>
    </form>
</body>
</html>
