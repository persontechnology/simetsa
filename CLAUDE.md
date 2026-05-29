# SIMETSA — Memoria del proyecto

Sistema Municipal de Estacionamiento Tarifado del Cantón Salcedo (`pry_simetsa`).
Base legal: **Ordenanza SIMETSA**, GAD Municipal de Salcedo, aprobada **06-feb-2020** y sancionada **10-feb-2020**.
**Citar el artículo correspondiente** en PHPDoc/comentarios cuando una regla provenga de la Ordenanza.
Texto canónico: `docs/legal/Ordenanza_SIMETSA.pdf`. Resumen operativo (artículo por artículo) para consulta rápida: `docs/legal/ordenanza-simetsa.md`.

Dos productos:

1. **Plataforma Web (Backoffice)** — administración municipal, Comisaría, Dirección de Seguridad, agentes y puntos de venta.
2. **Aplicación Móvil** — conductores y agentes en calle (consume la API REST).

---

## Stack

- **Laravel 11 puro** (sin Livewire / Inertia / Filament), **PHP 8.2+**, **PostgreSQL**.
- TZ `America/Guayaquil`, locale `es`.
- Web: Blade + Bootstrap 5 + Leaflet.js (tiles OSM).
- Móvil: React Native (JS, no TS) + react-native-maps (OSM) + API REST con Sanctum.
- Paquetes: Breeze (auth web), Sanctum (auth API), Spatie Permission (roles/permisos).
- Externos cerrados: **PayPhone** (pagos), **Firebase Cloud Messaging** (push), `storage/app/public` (archivos).

> Si necesitás un paquete nuevo, **proponelo con justificación** antes de instalarlo.

---

## Reglas estrictas

- **NO** modificar la estructura por defecto de Laravel, ni el modelo `User`, ni la tabla `users`, ni las migraciones originales de Laravel/Breeze/Sanctum/Spatie.
- Para extender el usuario usar el modelo **`PerfilUsuario`** (1:1) con el trait **`TienePerfilUsuario`**.
- Todo en **español**:
  - Modelos: singular (`Vehiculo`, `Zona`, `Plaza`).
  - Tablas: plural (`vehiculos`, `zonas`, `plazas`).
  - Controllers, rutas, variables, métodos, comentarios, mensajes al usuario.
- Permisos formato `modulo.accion` (ej. `agentes.editar`).
- **PHPDoc obligatorio** en clases y métodos públicos. Comentarios en bloques de lógica de negocio. Encabezado en cada archivo. **Citar la Ordenanza** cuando aplique.

### Convenciones detalladas por capa

- Controllers web y API: ver `app/Http/Controllers/CLAUDE.md`.
- Servicios (lógica de negocio): ver `app/Services/CLAUDE.md`.
- Vistas Blade + Bootstrap: ver `resources/views/CLAUDE.md`.

---

## Comandos artisan — regla de eficiencia

**Nunca** crear archivos uno por uno. Usar siempre flags combinados:

```bash
# Backoffice con vistas (8 archivos en un comando):
php artisan make:model NombreEntidad -mcrfs --requests --policy

# API móvil sin vistas (9 archivos en 2 comandos):
php artisan make:model NombreEntidad -mfs --api --requests --policy
php artisan make:resource NombreEntidadResource
```

Services/Actions se crean a mano en `app/Services` / `app/Actions`.

Tabla completa de flags y comandos complementarios (observer, test, job, notification, event/listener) en `docs/comandos-artisan.md`.

---

## Cumplimiento legal y privacidad

Diseñar pensando en la **LOPDP** (Ley Orgánica de Protección de Datos Personales del Ecuador): consentimiento informado al registrar, logs de auditoría para operaciones sensibles, política de retención.

---

## Roles del sistema

Vía Spatie (enum `App\Enums\RolSistema`):
`super_admin`, `comisario`, `director_seguridad`, `agente_parqueo`, `punto_venta`, `conductor`.

Usuarios de prueba sembrados por `UsuarioPruebaSeeder` (password = `password`):

| Correo | Rol |
|---|---|
| `admin@simetsa.gob.ec` | super_admin |
| `comisario@simetsa.gob.ec` | comisario |
| `director.seguridad@simetsa.gob.ec` | director_seguridad |
| `agente@simetsa.gob.ec` | agente_parqueo (AG-0001) |
| `puntoventa@simetsa.gob.ec` | punto_venta (PV-0001) |
| `conductor@simetsa.gob.ec` | conductor |

Cédulas válidas para tests: `1710034065`, `1102345677`. En tests de activación (perfil único) usar cédulas sintéticas `0999…` para no chocar con las sembradas.

---

## Estado actual

- **Fase 0** ✓ Configuración inicial.
- **Fase 1** ✓ Roles, permisos, perfiles de usuario.
- **Fase 2** ✓ Catálogos base (zonas, calles, manzanas, plazas, tipos, tarifas, horarios, feriados, parámetros).
- **Fase 3** ✓ Agentes de Parqueo (3.A–3.D) y Puntos de Venta (3.E.1–3.E.2).
- **Fase 4** ✓ Conductores y Vehículos (API móvil + backoffice supervisión). 4.A TipoVehiculo CRUD backoffice + API read-only. 4.B Vehiculo API CRUD (Sanctum, ownership). 4.C CredencialDiscapacidad CONADIS (Art. 26). 4.D Backoffice conductores + VehiculoExonerado (Art. 27). 55 tests.
- **Fase 5** ✓ Sistema de Tickets Digitales. 5.A modelos/migraciones/enums. 5.B TicketService (Arts. 12–14, 22, 26, 27) + 21 tests de borde. 5.C API conductor (comprar, historial, cancelar). 5.D API agente (validar placa, iniciar sesión). 5.E–5.F Backoffice supervisión y anulación. 5.G FCM placeholder (dispositivos + cola lógica). 68 tests. Decisiones: EstadoTicket como BackedEnum PHP 8.2, SesionParqueo 1:1 con Ticket, Cancelacion unifica conductor/admin con tipo enum.
- **Fase 6** ⏳ Integración PayPhone + FCM real. **Próximo paso.**

Roadmap completo (Fases 4–11 con detalle): ver `docs/roadmap-fases.md`.
Inventario de los ~55-60 modelos por módulo: ver `docs/inventario-modelos.md`.

---

## Deuda técnica abierta

- **`AgenteParqueoService::autorizar`** usa el patrón viejo de creación de perfil sin resolver identidad por cédula. Aplicar el mismo arreglo que `PuntoVentaService::activar` (resolución **cédula → correo → crear**) al volver sobre Fase 3. Alternativa: extraer un `ResolutorCuentaService` compartido.
- `UsuarioController` y `RolController` (Fase 1) usan `authorizeResource` en constructor (incompatible con Laravel 11); migrar a `HasMiddleware`.
- **Comando `simetsa:marcar-credenciales-vencidas`**: transicionar credenciales CONADIS con `fecha_vencimiento < today()` a estado `vencida`. Pendiente de Fase 6 o mantenimiento paralelo.
- **`VehiculoExonerado` sin suspensión temporal**: agregar acción `activar/desactivar` si el comisario necesita suspender una exoneración sin eliminarla (actualmente solo hay `activo` boolean).
- **Estados de ticket en BD**: el estado `en_tolerancia` / `expirado` se calcula en tiempo real en `TicketService::calcularEstadoActual()`; la BD puede quedar con estado `activo` si el vehículo superó su tiempo. Un comando artisan programado debería sincronizar los estados — pendiente de Fase 6.
- **`sesiones_parqueo.ver` no asignado a conductor**: el conductor ve la sesión embebida en `TicketResource` (relación `whenLoaded`); no tiene acceso directo al endpoint `/sesiones-parqueo/{id}`.

---

## Decisiones cerradas

- Pasarela de pagos: **PayPhone**.
- Push: **Firebase Cloud Messaging**.
- Mapas: **OpenStreetMap + Leaflet** (web) / **react-native-maps OSM** (móvil).
- Almacenamiento: disco local `public`.
- Comprobantes: nota de venta interna, preparado para facturación electrónica SRI a futuro.

---

## Metodología y formato de respuesta

- Trabajamos por **fases incrementales**: no avanzar a la siguiente hasta que la actual esté completa, probada y aprobada.
- Antes de generar código: **proponer estructura → comandos artisan combinados → esperar confirmación → generar código completo → sugerir pruebas**.
- Bloques de código con **path completo** como comentario inicial. Comandos artisan agrupados en un solo bloque bash. Resumen final con archivos creados, comandos a ejecutar, próximos pasos.
- No saltar fases ni adelantar funcionalidades sin pedido. No introducir paquetes/CSS distintos a los acordados. No modificar archivos base de Laravel sin avisar.
- Mantener vivo este archivo: actualizar **Estado actual** y **Deuda técnica** al cierre de cada sub-fase.