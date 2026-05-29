# Roadmap de fases

Trabajamos por **fases incrementales**. No avanzar a la siguiente fase hasta que la actual esté **completa, probada y aprobada**.

> El estado actual y el próximo paso viven en el `CLAUDE.md` raíz; acá está el detalle de cada fase.

---

## Fase 0 — Configuración inicial ✓

- Proyecto Laravel 11 creado (`pry_simetsa`).
- Breeze, Sanctum y Spatie Permission instalados.
- PostgreSQL configurado.

## Fase 1 — Roles, permisos y usuarios del sistema ✓

- Roles definidos: `super_admin`, `comisario`, `director_seguridad`, `agente_parqueo`, `punto_venta`, `conductor`.
- Permisos por módulo (`config/simetsa_permisos.php`).
- Modelo `PerfilUsuario` (extensión 1:1 del `User`).
- Seeder con usuarios de prueba para cada rol.
- Middleware `perfil.completo` para gateo de rutas operativas.
- Vistas Blade de gestión de usuarios y roles.

## Fase 2 — Catálogos base ✓

- Zonas tarifadas, calles, manzanas, plazas, tipos de plaza.
- Tarifas, horarios de operación, días feriados.
- Tabla de parámetros globales (SBU, tolerancia, tiempo máximo).
- CRUDs completos en backoffice con mapas Leaflet.

## Fase 3 — Agentes de Parqueo y Puntos de Venta ✓

**3.A** Solicitud + Documentación (Etapa 1, Art. 32-34): `SolicitudAgente`, `DocumentoAgente`, `SolicitudAgenteService`.
**3.B** Capacitación (Etapa 2, Art. 33.5): `CursoCapacitacion`, `InscripcionCurso`, `CalificacionCurso`, `CapacitacionService` (promedio ≥ 70).
**3.C** Autorización + Agente activo (Etapa 3, Art. 36): `AgenteParqueo`, `ExpedienteAgente`, `AgenteParqueoService` (crea cuenta con rol y perfil).
**3.D** Operación: `AsignacionZona` (Art. 16), `HorarioRotativo` (Art. 37.4), `AmonestacionAgente` (Art. 40, escalada verbal → escrita → terminación). Edición vía modales compartidos. Reglas relajadas: solo bloquear duplicado exacto.
**3.E.1** Solicitud de Punto de Venta + Documentación (Art. 31): `SolicitudPuntoVenta`, `DocumentoPuntoVenta`.
**3.E.2** Contrato + Punto de Venta activo (Art. 31 / 21): `ContratoPuntoVenta`, `PuntoVenta`, `PuntoVentaService` (resolución de identidad cédula → correo → crear, regla 3 cuadras, descuento 10%).

## Fase 4 — Conductores y Vehículos (API móvil) ✓

**Autenticación:** `AuthController` (registro público, login, logout, perfil), `ConductorService::registrar()` (crea `User` + rol `conductor` + `PerfilUsuario` + `Conductor` en una transacción). Tokenización vía Sanctum. Consentimiento LOPDP al registrar (Art. 7 LOPDP). `AutenticacionConductorTest` (9 tests).

**4.A** Catálogo `TipoVehiculo` (Art. 25): migración `tipos_vehiculo`, 6 tipos semilla (`liviano_privado`, `liviano_publico`, `taxi`, `furgoneta`, `carga_liviana`, `institucional`). CRUD backoffice (director/admin); endpoint API read-only `GET /api/v1/tipos-vehiculo` accesible a cualquier usuario autenticado. `TipoVehiculoControllerTest` (9 tests).

**4.B** Vehículos del conductor (Art. 25): `Vehiculo` + `VehiculoService` (`registrar`, `actualizar`, `eliminar`, `cambiarEstado`). Placa normalizada a mayúsculas; unicidad entre no-eliminados vía índice parcial PostgreSQL (`WHERE deleted_at IS NULL`). API REST completa (`apiResource /vehiculos`) con ownership: el conductor solo accede a sus propios vehículos (`VehiculoPolicy`). `VehiculoApiTest` (12 tests).

**4.C** Credencial CONADIS (Art. 26): `CredencialDiscapacidad` + `CredencialDiscapacidadService` (`solicitar`, `aprobar`, `rechazar`). Un vehículo solo puede tener una credencial activa (`pendiente`|`aprobada`) a la vez. El conductor la solicita desde la app; comisario/director la aprueba en el backoffice (`PATCH /credenciales-discapacidad/{id}/aprobar|rechazar`). Adjunto PDF/imagen en disco `public`. `CredencialDiscapacidadApiTest` (13 tests).

**4.D** Backoffice supervisión y exoneraciones (Art. 27, Art. 37): `ConductorController` (listado + detalle + bloquear/desbloquear, Art. 37); `VehiculoExonerado` + `VehiculoExoneradoController` (CRUD completo; sin FK a `vehiculos` — son vehículos institucionales: Policía, Bomberos, FF.AA., Municipal; tiempo máximo 2 horas, Art. 27). `ConductorService::cambiarEstado()`. Vistas Blade: `conductores/{index,show}`, `vehiculos-exonerados/{index,create,edit}`. `ConductorControllerTest` (8 tests), `VehiculoExoneradoControllerTest` (8 tests).

## Fase 5 — Sistema de Tickets Digitales ⏳ Próximo paso.

- Compra de tickets desde la app móvil.
- Tiempo máximo de 2 horas (Art. 14).
- Tolerancia de 5 minutos (Art. 13).
- Notificaciones FCM de tiempo restante.
- Historial de tickets por usuario.

## Fase 6 — Pasarela de Pagos PayPhone

- Integración con SDK de PayPhone (Laravel + React Native).
- Liquidación automática **60/40** (agentes) y **90/10** (puntos de venta) — Art. 21.
- Conciliación de pagos.
- Generación de comprobantes (nota de venta interna, base preparada para SRI).

## Fase 7 — Infracciones e Inmovilización

- Registro de infracciones desde la app del agente.
- Cálculo automático de multas (2%, 4%, 8%, 20%, 50% del SBU según Art. 28-30).
- Flujo de inmovilización y desinmovilización (Art. 15).
- Pago de multas en línea.

## Fase 8 — Reportes y Dashboard

- Reportes de recaudación por zona, agente, punto de venta.
- Reportes de infracciones.
- Ocupación por hora/día/zona.
- Dashboard con KPIs en tiempo real.
- Exportación a PDF y Excel.

## Fase 9 — Aplicación Móvil (React Native)

- App de Conductores (consumo completo de la API).
- App de Agentes de Parqueo (fiscalización en calle, mapa, escaneo de placas).
- Integración con FCM para push notifications.
- Integración con mapas OpenStreetMap (react-native-maps).

## Fase 10 — Integraciones Externas

- CONADIS (validación de discapacidad).
- ANT (validación de placas).
- ECU 911 (incidentes — Art. 38.m).
- Tesorería Municipal (cobros y conciliación).

## Fase 11 — Despliegue y Puesta en Producción

- Configuración del servidor (a definir según el GAD).
- Hardening, SSL, backups.
- Capacitación al personal del GAD.