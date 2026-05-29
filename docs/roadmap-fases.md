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

## Fase 4 — Conductores y Vehículos (API móvil) ⏳ próximo paso

- Registro y autenticación de conductores vía Sanctum.
- Gestión de vehículos (modelo, placa, color, tipo).
- Carga de credencial CONADIS (Art. 26).
- Endpoints API REST bajo `/api/v1/`.

## Fase 5 — Sistema de Tickets Digitales

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