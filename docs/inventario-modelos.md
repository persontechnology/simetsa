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

## Módulo de Pagos

- `Pago`.
- `MetodoPago` (efectivo, PayPhone tarjeta, PayPhone billetera, transferencia).
- `TransaccionPayphone` (logs de la pasarela).
- `Comprobante` (nota de venta inicialmente, factura electrónica a futuro).
- `LiquidacionAgente` (60/40 — Art. 21).
- `LiquidacionPuntoVenta` (90/10 — Art. 21).
- `ConciliacionPagos`.

## Módulo de Infracciones y Sanciones

- `Infraccion`.
- `TipoInfraccion` (catálogo según Art. 17 y Art. 18).
- `Multa` (porcentajes SBU — Art. 28, 29, 30).
- `Inmovilizacion` (candados aplicados — Art. 15).
- `OrdenPago` (generada por Comisaría — Art. 28).
- `NotificacionInfraccion` (boleta digital).
- `Impugnacion` (recursos contra una multa).

## Módulo de Fiscalización

- `TurnoAgente` (inicio/fin de jornada).
- `RecorridoAgente` (geolocalización en zona).
- `IncidenteCalle` (reportes desde la app del agente).
- `ReporteECU911` (Art. 38.m).

## Módulo de Reportes y Dashboard

- `ReporteGenerado` (cache de reportes pesados).
- `KPI` (indicadores precalculados).

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