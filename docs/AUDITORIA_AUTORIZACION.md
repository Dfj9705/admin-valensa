# Auditoría de autorización — Servicios Valensa

Fecha: 5 de noviembre de 2026.

Proyecto revisado y modificado: `C:\laragon\www\admin-valensa`.

## Alcance y resultado

Se revisaron los diez Resources solicitados, todos sus Pages, ambos Relation Managers, modelos, servicios de ventas y TEKRA, widgets, rutas y controlador de reportes. Los controles se ejecutan en el servidor; ocultar botones no constituye el control de seguridad.

La autorización sigue utilizando los roles y permisos existentes de Spatie. `Access` aplica límites por rol y comprueba los permisos configurados mediante Spatie. Se desactivó exclusivamente su registro automático en Gate para evitar que un permiso explícito eludiera esos límites; el Gate de la aplicación delega en `Access`. Se limpió la configuración compilada. No se ejecutaron seeders ni migraciones contra la base del negocio.

| Rol | Acceso |
|---|---|
| Superadministrador | Módulos completos; conserva las restricciones de integridad y de protección de cuentas/roles reservados que ya tenía el proyecto. |
| Administrador | Módulos de negocio según permisos; usuarios y roles quedan excluidos incluso si se le asignan permisos de esos módulos. |
| Vendedor | Ventas, pagos, productos, inventario y clientes según permisos; ninguna eliminación, aunque se le asigne accidentalmente ese permiso. |
| Invitado o usuario sin rol operativo | Acceso denegado. |

Los permisos pueden restringirse desde RoleResource. No se sobrescribieron las asignaciones existentes. Los componentes Livewire vuelven a comprobar acceso al montarse y en cada petición posterior. Las páginas de configuración de los plugins PWA/Settings Hub quedan reservadas al superadministrador.

## Cambios de integridad

- El stock continúa calculándose por la SUM de entradas y devoluciones menos salidas y ventas. Se conservó el accessor existente; no se mantiene una columna de stock.
- Toda inserción de movimientos mediante Eloquent bloquea el producto y mantiene validación y escritura dentro de la misma transacción. La comprobación de salida lee los movimientos con bloqueo para evitar un snapshot anterior de MySQL.
- Confirmación y anulación bloquean la venta, comprueban su estado actualizado y bloquean sus productos en orden estable. Un segundo intento no vuelve a descontar ni devolver existencias. Una venta certificada requiere el estado FEL de anulación antes de devolver stock.
- Se retiraron las acciones de edición/eliminación de movimientos. Los modelos también impiden estas operaciones: las correcciones deben representarse con movimientos compensatorios, conservando el historial. No se agregó un formulario general de reversión automática.
- No pueden borrarse ventas con certificación, pagos, vencimientos o movimientos. El borrado vuelve a comprobar el registro bloqueado. Productos, clientes, emisores y categorías con historial asociado tampoco se eliminan.
- El borrado masivo comprueba cada registro. En ventas conserva el flujo existente que anula ventas confirmadas, pero exige tanto eliminación como anulación y rechaza certificados/cancelados. El vendedor no accede a esta acción.
- Guardar una venta desde el formulario queda limitado a borradores; estado y campos FEL no se aceptan del formulario. La página sigue disponible para las acciones de pagos/FEL según sus permisos.
- Pagos: creación, edición y eliminación comprueban permisos y estado; se bloquea la venta durante validación y escritura. El monto debe ser positivo; la edición descuenta el pago actual del cálculo para impedir exceder el saldo.
- Crear clientes o categorías inline comprueba permiso tanto en la acción como en su callback de persistencia.
- Confirmar, certificar y anular tienen permisos independientes. Los servicios vuelven a autorizar antes de actuar. Las acciones FEL bloquean la venta durante llamada y persistencia para serializar solicitudes simultáneas de esa página.
- Los reportes exigen `reportes.ver` y `reportes.exportar`, incluso por URL directa. Los widgets comprueban lectura y el resumen de ventas no muestra gastos al vendedor.
- Los PDF nuevos de cotizaciones, recibos, facturas y anulaciones se guardan en el disco privado `local` y se descargan mediante la acción autorizada. Los PDF de reportes siguen entregándose desde sus rutas protegidas.
- Se retiró del manejo de errores de anulación SOAP el registro de solicitudes completas que podían contener credenciales FEL.

## Verificación

Las pruebas de autorización usan SQLite en memoria, con esquema y roles desechables. Cubren invitados, los tres roles, permisos revocados, asignaciones accidentales prohibidas, acceso directo a páginas y reportes, creación inline, permisos de acciones personalizadas, integridad histórica, stock insuficiente, productos duplicados en una venta, confirmación/anulación repetidas, pagos, descarga privada de PDF y guardado de ventas certificadas.

Certificación y anulación FEL se verificaron con respuestas simuladas: persistencia de UUID/estado y devolución única de stock. No se contactó TEKRA ni se emitieron/anularon documentos reales.

La prueba optativa de MySQL crea una base local nueva con nombre `valensa_auth_test_<aleatorio>`, inicia dos procesos PHP independientes y elimina únicamente esa base al finalizar. Con stock 5 y dos salidas simultáneas de 4, se verificó exactamente una salida aceptada, una rechazada y saldo 1. La base del negocio no interviene.

Para ejecutar todo, incluida concurrencia, desde PowerShell:

```powershell
$env:VALENSA_TEST_MYSQL = '1'
php vendor/bin/phpunit --do-not-cache-result
```

Sin esa variable la prueba de MySQL se omite. Requiere MySQL local y permiso para crear/eliminar su base temporal. La prueba de ejemplo se actualizó para verificar la redirección de invitados al login del panel, que ocupa la raíz del dominio.

Resultado final: **28 pruebas y 162 comprobaciones, todas correctas**, incluida la prueba optativa de concurrencia en MySQL. Se comprobó además la sintaxis de PHP y la ausencia de errores de espacios en el diff.

## Riesgos restantes y límites

1. **Documentos públicos históricos:** se contaron 20 archivos previos en `storage/app/public/fel`, `recibos` y `cotizaciones`. Se conservaron para no romper enlaces existentes. Las URL antiguas pueden seguir accesibles sin autorización hasta migrar esos archivos a almacenamiento privado y actualizar sus enlaces; los nuevos documentos ya son privados.
2. **FEL y recuperación:** SOAP y la base local no forman una transacción distribuida. Una aceptación remota seguida de fallo local exige conciliación con TEKRA. No se validó el servicio real. La interpretación heredada de respuestas de anulación debe contrastarse con ejemplos y contrato de TEKRA, especialmente respuestas vacías o no JSON. El bloqueo de la venta durante SOAP también implica espera para operaciones concurrentes sobre esa venta.
3. **Cantidades fraccionarias:** el detalle de ventas admite decimales, pero `mop_cantidad` y el accessor de stock son enteros. Se conservó ese contrato previo; debe definirse si el negocio permite fracciones antes de cambiarlo.
4. **Totales de venta:** la confirmación conserva el cálculo existente basado en `line_total` guardado. No se rediseñó la validación/recalculado de precios y descuentos; requiere revisión funcional separada si se necesita endurecimiento contra manipulación de importes.
5. **Acceso directo a base de datos:** los eventos de Eloquent no interceptan SQL directo, operaciones Query Builder ni cascadas de claves foráneas. El código normal revisado usa los controles añadidos. La FK heredada de movimientos todavía tiene borrado en cascada; una protección absoluta del historial frente a SQL directo requeriría restricciones/migraciones de base de datos y control de sus credenciales.
6. **Procesos sin sesión:** los servicios protegidos requieren un usuario autenticado. Cualquier futura tarea de consola/cola deberá establecer explícitamente su actor autorizado; no hay bypass automático para ejecución en consola.

No se hizo commit, push ni despliegue remoto. Las copias de los archivos originales se conservaron fuera del repositorio, en la carpeta inicial de trabajo `servicios valensa/authorization-before`.

## Archivos modificados y agregados

- `app/Filament/Pages/ReportadorFinanciero.php`
- `app/Filament/Resources/CategoriaGastoResource.php`
- `app/Filament/Resources/ClienteResource.php`
- `app/Filament/Resources/EgresoServicioResource.php`
- `app/Filament/Resources/EmisorResource.php`
- `app/Filament/Resources/GastoResource.php`
- `app/Filament/Resources/IngresoServicioResource.php`
- `app/Filament/Resources/ProductoResource/Pages/ListProductos.php`
- `app/Filament/Resources/ProductoResource/RelationManagers/MovimientosRelationManager.php`
- `app/Filament/Resources/ProductoResource.php`
- `app/Filament/Resources/RoleResource.php`
- `app/Filament/Resources/UserResource.php`
- `app/Filament/Resources/VentaResource/Pages/CreateVenta.php`
- `app/Filament/Resources/VentaResource/Pages/EditVenta.php`
- `app/Filament/Resources/VentaResource/Pages/ListVentas.php`
- `app/Filament/Resources/VentaResource/RelationManagers/PagosRelationManager.php`
- `app/Filament/Resources/VentaResource/Widgets/VentaStats.php`
- `app/Filament/Resources/VentaResource.php`
- `app/Filament/Widgets/InventoryStats.php`
- `app/Filament/Widgets/LatestMovements.php`
- `app/Filament/Widgets/MovementsChart.php`
- `app/Filament/Widgets/ServiciosResumen.php`
- `app/Http/Controllers/ReportesController.php`
- `app/Models/CategoriaGasto.php`
- `app/Models/Cliente.php`
- `app/Models/Emisor.php`
- `app/Models/Producto.php`
- `app/Models/ProductoMovimiento.php`
- `app/Models/Venta.php`
- `app/Providers/AppServiceProvider.php`
- `app/Providers/AuthServiceProvider.php`
- `app/Services/Sales/ConfirmVenta.php`
- `app/Services/Sales/ManagePago.php`
- `app/Services/Tekra/TekraContribuyenteService.php`
- `app/Services/Tekra/TekraFelService.php`
- `app/Support/Access.php`
- `config/permission.php`
- `routes/web.php`
- `tests/Feature/AuthorizationIntegrityTest.php`
- `tests/Feature/ExampleTest.php`
- `tests/Feature/MySqlStockConcurrencyTest.php`
- `tests/Support/stock-worker.php`
- `docs/AUDITORIA_AUTORIZACION.md`
