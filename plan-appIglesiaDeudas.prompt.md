## Plan: App de deudas de actividades

Vamos a crear un proyecto PHP puro con MySQL/MariaDB en este workspace. La app tendra un panel admin para usuarios y asignaciones por fecha, y un acceso por link con token valido por 1 dia para el responsable de ese sabado. La vista abierta sera publica con tabla de deudas por persona, con filtros por fecha y actividad. Se usara borrado logico para usuarios. Tambien se registraran ajustes por descuentos o combos como valor arbitrario por deudor. Enviaras el link manualmente desde el admin. El pago de deudas solo lo puede registrar el responsable el mismo dia de la actividad; el resto de dias solo el admin. El admin podra saldar deudas por persona, por actividad o todas a la vez de forma sencilla.

**Steps**
1. Definir estructura base del proyecto PHP y configuracion de entorno con conexion a DB y routing simple en [public/index.php](public/index.php), [config/config.php](config/config.php), y helpers en [src/bootstrap.php](src/bootstrap.php).
2. Disenar esquema de base de datos: usuarios (admin y responsables), asignaciones por fecha, actividades, deudores, pagos/saldos, y tokens de acceso; documentar en [db/schema.sql](db/schema.sql).
3. Implementar autenticacion admin con sesiones y pantalla de login en [public/admin/login.php](public/admin/login.php).
4. Crear CRUD de usuarios (crear, editar, desactivar) y asignaciones de responsables por fecha, mas generacion de token de 1 dia y link copiable en [public/admin/users.php](public/admin/users.php) y [public/admin/assignments.php](public/admin/assignments.php).
5. Implementar acceso por token para responsables en [public/activity/index.php](public/activity/index.php); validar fecha y estado del token; formulario para registrar actividad y lista de deudores.
6. Guardar actividad y deudores con unidades y/o valor arbitrario, y generar resumen visible en [public/activity/summary.php](public/activity/summary.php).
7. Implementar reglas de saldar deudas: el responsable solo puede registrar pagos el mismo dia; el admin puede hacerlo cualquier dia, por persona, por actividad o de forma masiva desde el panel admin.
8. Construir vista publica de deudas con filtros por fecha y actividad, y tabla agregada por persona en [public/public/index.php](public/public/index.php) con consultas en [src/repositories](src/repositories).
9. Endurecer seguridad basica: validacion de inputs, CSRF en formularios admin, tokens aleatorios, y restricciones de acceso.

**Verification**
- Crear base en MySQL y ejecutar [db/schema.sql](db/schema.sql).
- Correr servidor local con `php -S localhost:8000 -t public`.
- Probar flujo: admin crea usuario, asigna fecha, copia link, responsable registra actividad, vista publica muestra deudas.

**Decisions**
- Acceso de responsables por link con token valido 1 dia, sin password.
- Vista de deudas publica.
- PHP puro y MySQL/MariaDB.
- Solo el responsable puede registrar pagos el mismo dia; el admin puede saldar cualquier dia y en lote.
