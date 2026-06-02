# Inventario de modelos (~55-60 entidades)

Alcance real del sistema, agrupado por módulo. **No** limitarse a los modelos ya implementados: SIMETSA es un sistema completo. Si durante el desarrollo de una fase identificás que falta una entidad necesaria, **proponela con justificación antes de crearla**.

---

## Módulo de Seguridad y Acceso

- `PerfilUsuario` (extiende `User` con cédula, teléfono, dirección).
- `RegistroAcceso` (log de login/logout).
- `RegistroAuditoria` (auditoría general).
- Roles vía Spatie: `super_admin`, `comisario`, `director_seguridad`, `agente_parqueo`, `punto_venta`, `conductor`.

## Módulo de Catálogos Base

- `Zona`.
- `Calle` (calles de cada zona — Art. 16).
- `Manzana` (codificación urbana — Art. 10).
- `Plaza` (espacios individuales de estacionamiento).
- `TipoPlaza` (normal, discapacidad, taxi, carga, autoridad).
- `Tarifa` (parametrizable por tipo y horario).
- `HorarioOperacion` (martes-viernes y domingo, 08:00-18:00 — Art. 12).
- `DiaFeriado` (feriados, cívicos, fiestas cantonales).
- `Parametro` (SBU vigente, tolerancia, tiempo máximo, etc.).

## Módulo de Conductores y Vehículos

- `Conductor`.
- `Vehiculo`.
- `TipoVehiculo` (liviano público/privado, taxi, furgoneta, turismo, institucional, carga).
- `CredencialDiscapacidad` (CONADIS — Art. 26).
- `VehiculoExonerado` (oficiales — Art. 27).

## Módulo de Agentes de Parqueo

- `AgenteParqueo`.
- `SolicitudAgente` (proceso de postulación).
- `DocumentoAgente` (cédula, antecedentes, certificados).
- `CursoCapacitacion` (Atención al Cliente, Primeros Auxilios, Educación Vial).
- `InscripcionCurso`.
- `CalificacionCurso` (mínimo 70/100 — Art. 33).
- `AsignacionZona`.
- `HorarioRotativo` (Art. 37.4).
- `AmonestacionAgente` (verbal, escrita, terminación — Art. 40).
- `ExpedienteAgente`.

## Módulo de Puntos de Venta

- `PuntoVenta`.
- `SolicitudPuntoVenta`.
- `ContratoPuntoVenta` (Procuraduría Síndica — Art. 31).
- `DocumentoPuntoVenta`.

## Módulo de Tickets y Operación

- `Ticket` (ticket digital — reemplazo del Art. 19).
- `EstadoTicket` (activo, expirado, anulado, en tolerancia).
- `SesionParqueo` (inicio/fin del estacionamiento).
- `Cancelacion` (anulación con motivo).

## Módulo de Pagos ✓ (Fase 6)

- `TransaccionPago` ✓ (polimórfica via `concepto_type/concepto_id`; registra pagos de Tickets e Infracciones). Reemplaza el modelo `Pago` original.
- `MetodoPago` enum ✓ (efectivo, tarjeta, billetera, transferencia).
- `ProveedorPago` enum ✓ (none, manual, deuna, pagomedios).
- `Comprobante` (nota de venta — pendiente Fase 9+).
- `LiquidacionAgente` (60/40 — Art. 21 — pendiente Fase 9+).
- `LiquidacionPuntoVenta` (90/10 — Art. 21 — pendiente Fase 9+).
- `ConciliacionPagos` (pendiente Fase 10).

## Módulo de Infracciones y Sanciones ✓ (Fase 7)

- `Infraccion` ✓ (implements `Cobrable`, snapshot `monto_multa`+`sbu_vigente`).
- `TipoInfraccion` enum ✓ (12 casos cerrados por Ordenanza: Arts. 17+18).
- `EstadoInfraccion` enum ✓ (pendiente → pagada/anulada).
- `Inmovilizacion` ✓ (candados — Art. 15, 1:1 con Infraccion).
- `EstadoInmovilizacion` enum ✓ (activa → liberada/anulada).
- `OrdenPago` (Comisaría — Art. 28 — pendiente Fase 9+).
- `NotificacionInfraccion` (boleta digital — pendiente Fase 9+).
- `Impugnacion` (recursos — pendiente Fase 9+).

## Módulo de Fiscalización

- `TurnoAgente` (inicio/fin de jornada — pendiente Fase 9+).
- `RecorridoAgente` (geolocalización en zona — pendiente Fase 9+).
- `IncidenteCalle` (reportes desde la app del agente — pendiente Fase 9+).
- `ReporteECU911` (Art. 38.m — pendiente Fase 10).

## Módulo de Reportes y Dashboard ✓ (Fase 8)

No se crearon modelos nuevos: los reportes consultan directamente sobre modelos existentes via `ReporteService`.
- `ReporteGenerado` — **descartado**: la caché Laravel 5 min cubre el caso de uso sin tabla persistente.
- `KPI` — **descartado**: los KPIs se calculan on-demand y se cachean en Redis/file; no requieren tabla.
- `app/Services/ReporteService.php` ✓ — centraliza todas las queries de dashboard y reportes.
- `app/Exports/RecaudacionExport.php` ✓, `InfraccionesExport.php` ✓ — Maatwebsite Excel.
- `resources/views/layouts/impresion.blade.php` ✓ — layout sin sidebar para PDF Blade imprimible.

## Módulo de Notificaciones

- `Notificacion` (tabla nativa de Laravel `notifications`).
- `DispositivoMovil` (tokens FCM por usuario).
- `PreferenciaNotificacion`.

## Módulo de Integraciones Externas

- `LogIntegracionCONADIS`.
- `LogIntegracionANT`.
- `LogIntegracionTesoreria`.
- `LogIntegracionECU911`.
- `LogIntegracionPayphone`.