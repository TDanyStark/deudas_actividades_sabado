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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Acceso admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600&family=Sora:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="<?php echo url('/assets/app.css'); ?>" rel="stylesheet">
</head>
<body>
    <main class="app-shell app-shell--center">
        <section class="app-card">
            <h1 class="mb-3">Acceso admin</h1>
            <p class="muted mb-4">Ingresa para administrar usuarios, asignaciones y deudas.</p>

            <?php if ($error): ?>
                <div class="alert alert-danger mb-3" role="alert"><?php echo h($error); ?></div>
            <?php endif; ?>

            <form method="post" class="d-grid gap-3">
                <?php echo csrf_field(); ?>
                <div>
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div>
                    <label class="form-label">Clave</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary">Entrar</button>
            </form>
        </section>
    </main>
</body>
</html>
