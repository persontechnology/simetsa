<?php
// config/simetsa_permisos.php

/**
 * Catálogo centralizado de permisos del SIMETSA.
 *
 * Estructura: ['grupo_funcional' => ['entidad' => ['accion', 'accion', ...]]]
 * Cada permiso se genera con la convención `entidad.accion` (ej: usuarios.ver).
 *
 * Este archivo es la fuente única de verdad para el RolPermisoSeeder.
 * Agregar/quitar permisos aquí y re-ejecutar el seeder.
 *
 * Fuente legal: Ordenanza de Creación y Funcionamiento del SIMETSA del
 * cantón Salcedo (aprobada 06-feb-2020, sancionada 10-feb-2020).
 */

return [

    // ===== Seguridad y acceso =====
    'seguridad' => [
        'usuarios'  => ['ver', 'crear', 'editar', 'eliminar'],
        'roles'     => ['ver', 'crear', 'editar', 'eliminar', 'asignar'],
        'perfiles'  => ['ver', 'editar'],
        'accesos'   => ['ver'],
        'auditoria' => ['ver'],
    ],

    // ===== Catálogos base (Art. 5, 6, 10, 12, 16) =====
    'catalogos' => [
        'zonas'        => ['ver', 'crear', 'editar', 'eliminar'],
        'calles'       => ['ver', 'crear', 'editar', 'eliminar'],
        'manzanas'     => ['ver', 'crear', 'editar', 'eliminar'],
        'plazas'       => ['ver', 'crear', 'editar', 'eliminar'],
        'tipos_plaza'  => ['ver', 'crear', 'editar', 'eliminar'],
        'tarifas'      => ['ver', 'crear', 'editar', 'eliminar'],
        'horarios'     => ['ver', 'crear', 'editar', 'eliminar'],
        'feriados'     => ['ver', 'crear', 'editar', 'eliminar'],
        'parametros'   => ['ver', 'editar'],
    ],

    // ===== Conductores y vehículos (Art. 25, 26, 27) =====
    'conductores' => [
        'conductores'              => ['ver', 'crear', 'editar', 'eliminar'],
        'vehiculos'                => ['ver', 'crear', 'editar', 'eliminar'],
        'tipos_vehiculo'           => ['ver', 'crear', 'editar', 'eliminar'],
        'credenciales_discapacidad'=> ['ver', 'crear', 'editar', 'aprobar'],
        'vehiculos_exonerados'     => ['ver', 'crear', 'editar', 'eliminar'],
    ],

    // ===== Agentes de parqueo (Art. 32-40) =====
    'agentes' => [
        'agentes'             => ['ver', 'crear', 'editar', 'eliminar'],
        'solicitudes_agente'  => ['ver', 'crear', 'aprobar', 'rechazar'],
        'documentos_agente'   => ['ver', 'subir', 'eliminar'],
        'cursos'              => ['ver', 'crear', 'editar', 'eliminar'],
        'inscripciones'       => ['ver', 'crear', 'eliminar'],
        'calificaciones'      => ['ver', 'registrar'],
        'asignaciones_zona'   => ['ver', 'asignar'],
        'horarios_rotativos'  => ['ver', 'crear', 'editar'],
        'amonestaciones'      => ['ver', 'registrar'],
        'expedientes_agente'  => ['ver'],
    ],

    // ===== Puntos de venta (Art. 31) =====
    'puntos_venta' => [
        'puntos_venta'             => ['ver', 'crear', 'editar', 'eliminar'],
        'solicitudes_punto_venta'  => ['ver', 'crear', 'aprobar', 'rechazar'],
        'contratos_punto_venta'    => ['ver', 'crear', 'editar'],
        'documentos_punto_venta'   => ['ver', 'subir', 'eliminar'],
    ],

    // ===== Tickets y operación (Art. 13, 14, 19, 22, 24) =====
    'tickets' => [
        'tickets'           => ['ver', 'comprar', 'cancelar', 'anular'],
        'sesiones_parqueo'  => ['ver', 'iniciar'],
        'cancelaciones'     => ['ver', 'registrar'],
    ],

    // ===== Pagos y liquidaciones (Art. 21, 22) =====
    'pagos' => [
        'pagos'                 => ['ver', 'registrar'],
        'transacciones_payphone'=> ['ver'],
        'comprobantes'          => ['ver', 'emitir'],
        'liquidaciones'         => ['ver', 'generar'],
        'conciliaciones'        => ['ver', 'ejecutar'],
    ],

    // ===== Infracciones y sanciones (Art. 15, 17, 28, 29, 30) =====
    'infracciones' => [
        'infracciones'              => ['ver', 'registrar'],
        'multas'                    => ['ver', 'calcular'],
        'inmovilizaciones'          => ['ver', 'aplicar', 'retirar'],
        'ordenes_pago'              => ['ver', 'generar'],
        'notificaciones_infraccion' => ['ver', 'enviar'],
        'impugnaciones'             => ['ver', 'registrar', 'resolver'],
    ],

    // ===== Fiscalización en calle (Art. 38) =====
    'fiscalizacion' => [
        'turnos'           => ['ver', 'iniciar', 'cerrar'],
        'recorridos'       => ['ver'],
        'incidentes'       => ['ver', 'registrar'],
        'reportes_ecu911'  => ['ver', 'enviar'],
    ],

    // ===== Reportes y dashboard =====
    'reportes' => [
        'reportes' => ['ver', 'generar', 'exportar'],
        'kpi'      => ['ver'],
    ],

    // ===== Notificaciones =====
    'notificaciones' => [
        'notificaciones'           => ['ver'],
        'dispositivos_moviles'     => ['ver', 'registrar'],
        'preferencias_notificacion'=> ['ver', 'editar'],
    ],

    // ===== Integraciones externas =====
    'integraciones' => [
        'logs_integraciones' => ['ver'],
    ],

];