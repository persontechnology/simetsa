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

## Fase 5 — Sistema de Tickets Digitales ✓

**Decisiones de diseño:** `EstadoTicket` como `BackedEnum` PHP 8.2; `SesionParqueo` tabla separada 1:1 con `Ticket`; `Cancelacion` unifica baja-conductor y anulación-admin con discriminador `tipo` enum; `zona_id` obligatorio + `calle_id` opcional en ticket; fallback $0.25/hora (Art. 22) si sin tarifa vigente; cruce de jornada rechazado con mensaje orientativo.

**5.A** Modelos, migraciones y enums: `Ticket`, `SesionParqueo`, `Cancelacion`, `DispositivoMovil`, `NotificacionPush`. Enums: `EstadoTicket`, `EstadoSesionParqueo`, `MetodoPago`, `TipoCancelacion`. Policies: `TicketPolicy` (ownership conductor), `SesionParqueoPolicy`.

**5.B** `TicketService` con todas las reglas de la Ordenanza: `comprar`, `calcularMonto`, `validarHorarioYFeriado`, `validarMaximoHoras`, `validarPorPlaca` (tolerancia Art. 13), `cancelar`, `anular`. 21 tests de borde (Arts. 12, 13, 14, 22, 26, 27).

**5.C** API móvil conductor: `GET /api/v1/tickets` (vigentes), `POST /api/v1/tickets` (comprar), `GET /api/v1/tickets/historial`, `GET /api/v1/tickets/{id}`, `POST /api/v1/tickets/{id}/cancelar`. `TicketResource`, `SesionParqueoResource`. 15 tests. `docs/api/tickets.md`.

**5.D** API agente en calle: `GET /api/v1/tickets/validar/{placa}` (estado + tolerancia), `POST /api/v1/sesiones-parqueo` (iniciar), `GET /api/v1/sesiones-parqueo/{id}`. `SesionParqueoService`. 13 tests. `docs/api/sesiones.md`.

**5.E–5.F** Backoffice supervisión y anulación: `TicketController` web (index + show + anular), vistas `tickets/{index,show}.blade.php`, modal de anulación. Acceso por rol (super_admin|comisario|director_seguridad). 9 tests.

**5.G** FCM placeholder: `POST /api/v1/dispositivos` (registrar/actualizar token, idempotente), `DELETE /api/v1/dispositivos/{token}`. `NotificacionPushService::encolar()` / `marcarEnviada()`. 10 tests. `docs/api/dispositivos.md`.

**Total Fase 5:** 68 tests, 7 commits, 3 docs de API.

## Fase 6 — Pagos multi-proveedor + FCM real ✓

**6.0** Cleanup: MetodoPago → dos campos (`metodo_pago` + `proveedor`), enum `ProveedorPago`, gate `PagoSimulado` por entorno, comando `simetsa:sincronizar-estados-tickets` (deuda técnica #5 resuelta), eliminación de PayPhone.

**6.A** Arquitectura multi-proveedor: interfaces `Cobrable` + `PaymentProviderInterface`, modelo `TransaccionPago` (polimórfico, softDeletes), `DeunaPaymentProvider` en modo fake (sin HTTP externo), `PagoManager` singleton. `Http::assertNothingSent()` garantiza seguridad.

**6.B** FCM real: `kreait/laravel-firebase` (^7.2), `EnviarNotificacionFCMJob` (tries=3, backoff exponencial, lazy FCMService), interruptor `FCM_ENABLED`, columnas `fallida_en/ultimo_error/omitida` en `notificaciones_push`.

**6.C** Integración: `EstadoTicket::PendientePago`, `EstadoReembolso` en `Cancelacion`, `PagoWebhookController` (POST `/api/v1/pagos/webhook/{proveedor}`, público, idempotente), `Ticket::acreditar()` transiciona estado.

**Total Fase 6:** 35 tests nuevos, 393 passing. Decisiones: proveedor explícito en request, webhook registrado desde inicio (fake acepta cualquier firma), kreait/laravel-firebase sobre firebase-php puro, `calcularEstadoActual()` público en `TicketService`.

**Pendiente para producción:** credenciales Deuna reales (`DEUNA_ENABLED=true`), credencial Firebase (`FIREBASE_CREDENTIALS`), reembolso automático vía Deuna (requiere endpoint oficial).

## Fase 7 — Infracciones e Inmovilización ✓

**7.A** Modelos y fundación: `Infraccion` + `Inmovilizacion` (1:1 nullable, Art. 15). Enums: `TipoInfraccion` (12 casos, Arts. 17+18), `EstadoInfraccion` (pendiente → pagada/anulada), `EstadoInmovilizacion` (activa → liberada/anulada). `Infraccion implements Cobrable` (morph `concepto`, integración Fase 6). Policies: agente solo ve las suyas; comisario/director bypass. Factories con states `tiempoExcedido`, `pagada`, `liberada`.

**7.B** `InfraccionService`: `calcularMulta()` (tabla escalonada Art. 28: 2/4/8%; fijos Art. 29: 2/20%; agresión Art. 30: 50%; NegarPago: 0), `registrar()` (snapshot SBU, normalización placa), `inmovilizar()`, `liberar()` (Art. 15: pago previo o motivo administrativo), `anular()` (cascada sobre inmovilización activa). 27 tests de borde.

**7.C** API agente en calle: `POST /api/v1/infracciones` (registrar + multa automática), `GET /api/v1/infracciones/{id}` (detalle con inmovilización), `POST /{id}/inmovilizar` (Art. 15), `POST /{id}/liberar`. Permisos: `infracciones.registrar`, `inmovilizaciones.aplicar`, `inmovilizaciones.retirar` (agregados al rol `agente_parqueo`). `docs/api/infracciones.md`. 14 tests.

**7.D** API conductor: `GET /api/v1/conductor/infracciones` (historial por placa de vehículos + conductor_id), `POST /api/v1/infracciones/{id}/pagar` (inicia cobro vía `PagoManager`). Webhook genérico existente (`PagoWebhookController`) acredita el pago sobre `Infraccion` sin cambios → llama `acreditar()` → estado `pagada` + inmovilización `liberada` (Art. 15 end-to-end). 11 tests.

**7.E** Backoffice supervisión: `InfraccionController` web (index con 7 filtros, show con inmovilización + transacciones embebidas, anular con modal). Vistas Blade: `infracciones/index.blade.php`, `infracciones/show.blade.php`. Acceso: super_admin/comisario/director. `AnularInfraccionRequest`. Rutas + breadcrumbs. 10 tests.

**Total Fase 7:** 80 tests nuevos, 473 passing. 5 commits. Decisiones: `TipoInfraccion` BackedEnum PHP 8.2 (catálogo cerrado por Ordenanza), `Inmovilizacion` entidad propia (agente puede diferir del que registra la infracción), `monto_multa` persistido con snapshot `sbu_vigente`, `NegarPago` (Art. 17.g) registrable sin cargo económico, `conductor_id` nullable en `Infraccion`.

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