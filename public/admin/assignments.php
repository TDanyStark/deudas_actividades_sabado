<?php

require __DIR__ . '/../../src/bootstrap.php';
require __DIR__ . '/../../src/repositories/index.php';

require_admin();

$notice = '';

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $user_id = (int)($_POST['user_id'] ?? 0);
        $activity_date = $_POST['activity_date'] ?? '';
        if ($user_id && $activity_date) {
            assignment_create($user_id, $activity_date);
            $notice = 'Asignacion creada.';
        }
    }

    if ($action === 'regenerate') {
        $assignment_id = (int)($_POST['assignment_id'] ?? 0);
        if ($assignment_id) {
            assignment_regenerate_token($assignment_id);
            $notice = 'Token regenerado.';
        }
    }
}

$users = array_filter(user_all(), function ($user) {
    return $user['role'] === 'responsable' && (int)$user['active'] === 1;
});

$page = (int)($_GET['page'] ?? 1);
$page = $page > 0 ? $page : 1;
$per_page = 15;
$total = assignment_count();
$total_pages = max(1, (int)ceil($total / $per_page));
if ($page > $total_pages) {
    $page = $total_pages;
}
$offset = ($page - 1) * $per_page;
$assignments = assignment_paginated($per_page, $offset);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Asignaciones</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600&family=Sora:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="<?php echo url('/assets/app.css'); ?>" rel="stylesheet">
</head>
<body>
    <main class="app-shell">
        <header class="app-header mb-4">
            <div>
                <h1>Asignaciones</h1>
                <p class="muted mb-0">Crea accesos diarios para responsables.</p>
            </div>
            <nav class="app-nav">
                <a href="<?php echo url('/admin/users'); ?>">Usuarios</a>
                <a href="<?php echo url('/admin/debts'); ?>">Deudas</a>
                <a href="<?php echo url('/admin/logout'); ?>">Salir</a>
            </nav>
        </header>

        <?php if ($notice): ?>
            <div class="alert alert-success mb-4" role="alert"><?php echo h($notice); ?></div>
        <?php endif; ?>

        <section class="app-card mb-4">
            <h2 class="section-title">Nueva asignacion</h2>
            <form method="post" class="row g-3 align-items-end">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="create">
                <div class="col-md-6">
                    <label class="form-label">Responsable</label>
                    <select name="user_id" class="form-select" required>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo (int)$user['id']; ?>"><?php echo h($user['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Fecha actividad</label>
                    <input type="date" name="activity_date" class="form-control" required>
                </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-primary">Asignar</button>
                </div>
            </form>
        </section>

        <section class="app-card">
            <h2 class="section-title">Listado</h2>
            <div class="table-responsive">
                <table class="table table-dark table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Responsable</th>
                            <th>Email</th>
                            <th>Token</th>
                            <th>Link</th>
                            <th>Accion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assignments as $assignment): ?>
                            <?php
                                $link = url('/activity?token=' . $assignment['token']);
                            ?>
                            <tr>
                                <td><?php echo h($assignment['activity_date']); ?></td>
                                <td><?php echo h($assignment['user_name']); ?></td>
                                <td><?php echo h($assignment['user_email']); ?></td>
                                <td><?php echo h($assignment['token']); ?></td>
                                <td>
                                    <div class="d-flex align-items-start gap-2">
                                        <textarea readonly rows="2" class="form-control form-control-sm assignment-link"><?php echo h($link); ?></textarea>
                                        <button type="button" class="btn btn-outline-light btn-sm copy-link" data-link="<?php echo h($link); ?>" aria-label="Copiar link" title="Copiar link">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                                <path d="M10 1.5A1.5 1.5 0 0 1 11.5 3v7A1.5 1.5 0 0 1 10 11.5H5A1.5 1.5 0 0 1 3.5 10V3A1.5 1.5 0 0 1 5 1.5h5zm0 1H5a.5.5 0 0 0-.5.5v7a.5.5 0 0 0 .5.5h5a.5.5 0 0 0 .5-.5V3a.5.5 0 0 0-.5-.5z"/>
                                                <path d="M12.5 4a.5.5 0 0 1 .5.5v7A2.5 2.5 0 0 1 10.5 14h-5a.5.5 0 0 1 0-1h5A1.5 1.5 0 0 0 12 11.5v-7a.5.5 0 0 1 .5-.5z"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                                <td>
                                    <form method="post">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="action" value="regenerate">
                                        <input type="hidden" name="assignment_id" value="<?php echo (int)$assignment['id']; ?>">
                                        <button type="submit" class="btn btn-outline-light btn-sm">Regenerar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($total_pages > 1): ?>
                <nav class="mt-3" aria-label="Paginacion de asignaciones">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>">Anterior</a>
                        </li>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>">Siguiente</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </section>
    </main>
    <script>
        (function () {
            function fallbackCopy(text) {
                var textarea = document.createElement('textarea');
                textarea.value = text;
                textarea.setAttribute('readonly', '');
                textarea.style.position = 'absolute';
                textarea.style.left = '-9999px';
                document.body.appendChild(textarea);
                textarea.select();
                try {
                    document.execCommand('copy');
                } catch (err) {
                    return false;
                } finally {
                    document.body.removeChild(textarea);
                }
                return true;
            }

            document.querySelectorAll('.copy-link').forEach(function (button) {
                button.addEventListener('click', function () {
                    var link = button.dataset.link || '';
                    if (!link) {
                        return;
                    }
                    var setFeedback = function () {
                        var originalTitle = button.title;
                        var originalLabel = button.getAttribute('aria-label');
                        button.title = 'Copiado';
                        button.setAttribute('aria-label', 'Copiado');
                        setTimeout(function () {
                            button.title = originalTitle;
                            button.setAttribute('aria-label', originalLabel || 'Copiar link');
                        }, 1200);
                    };

                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(link).then(setFeedback).catch(function () {
                            if (fallbackCopy(link)) {
                                setFeedback();
                            }
                        });
                    } else if (fallbackCopy(link)) {
                        setFeedback();
                    }
                });
            });
        })();
    </script>
</body>
</html>
