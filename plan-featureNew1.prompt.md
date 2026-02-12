## Plan: Autocompletar deudores, calculo por unidad y admin crea deudas

Integra tres mejoras: (1) el nombre del deudor se autocompleta con nombres existentes y se guarda automaticamente si es nuevo; (2) el valor de la actividad se trata como precio por unidad, con override manual por unidad y recalculo inmediato; (3) el admin puede crear deudas en cualquier fecha desde /admin/debts. Todo mantiene el esquema actual sin tabla de clientes y aplica la misma logica de calculo tanto para responsables como para admin.

**Steps**
1. Crear `debtor_names()` en [src/repositories/DebtRepository.php](src/repositories/DebtRepository.php#L1-L90) para devolver nombres unicos ordenados y reusarlo en vistas.
2. Actualizar el formulario de deudor en [public/activity/index.php](public/activity/index.php#L150-L200) para usar `datalist` con `debtor_names()` y mantener el guardado automatico de nombres nuevos al crear la deuda.
3. Ajustar la logica de calculo en [public/activity/index.php](public/activity/index.php#L150-L200): si hay valor de actividad, calcular total como `unidades * valorActividad`; si hay override por unidad, usar ese; si no hay valor de actividad ni override, exigir monto manual. Recalcular en formulario con JS y validar/calcular en servidor.
4. Agregar en /admin/debts una seccion “Crear deuda (admin)” con selector searchable de actividades (label “fecha — nombre”), campos de deudor con autocompletado, unidades, override por unidad y total calculado, en [public/admin/debts.php](public/admin/debts.php#L51-L170).
5. Implementar handler POST en [public/admin/debts.php](public/admin/debts.php#L10-L50) para crear deudas usando la misma logica de calculo y `debtor_create()` en [src/repositories/DebtRepository.php](src/repositories/DebtRepository.php#L1-L15).
6. Ajustar listado de actividades para el selector admin: reutilizar o extender `activity_list()` para exponer label con fecha y nombre en [src/repositories/ActivityRepository.php](src/repositories/ActivityRepository.php#L18-L45).
7. Ordenar todas las consultas de tablas de mas nuevo a mas viejo, usando `ORDER BY` descendente (por `created_at` o `id` segun aplique) en los metodos de repositorio que alimentan listados en [src/repositories](src/repositories).
8. Implementar paginacion para listados largos, priorizando asignaciones en [public/admin/assignments.php](public/admin/assignments.php#L1-L150): agregar parametros `page` y `per_page`, conteo total, y controles de navegacion.

**Verification**
- Crear deuda como responsable en actividad con valor: total auto, override por unidad funciona.
- Crear deuda en actividad sin valor: total manual requerido.
- Autocompletado de deudores muestra nombres existentes y un nombre nuevo aparece tras guardar.
- Admin crea deuda para actividad pasada y futura desde /admin/debts.
- Verificar que todas las tablas muestran primero los registros mas nuevos.
- Verificar paginacion en asignaciones y que navegar entre paginas conserva filtros actuales si existen.

**Decisions**
- Sin nueva tabla de clientes; autocompletar desde nombres existentes.
- Valor de actividad es precio por unidad; override manual por unidad recalcula total.
- Admin sin restricciones de fecha y con formulario en /admin/debts.
- Orden descendente para listados (mas nuevo primero) y paginacion en tablas largas.
