<?php // routes/breadcrumbs.php

// Note: Laravel will automatically resolve `Breadcrumbs::` without
// this import. This is nice for IDE syntax and refactoring.
use Diglactic\Breadcrumbs\Breadcrumbs;

// This import is also not required, and you could replace `BreadcrumbTrail $trail`
//  with `$trail`. This is nice for IDE type checking and completion.
use Diglactic\Breadcrumbs\Generator as BreadcrumbTrail;

// Dashboard
Breadcrumbs::for('dashboard', function (BreadcrumbTrail $trail) {
    $trail->push('Dashboard', route('dashboard'));
});

/* --------------------------------------- */
// Dashboard > Lista de Usuarios
Breadcrumbs::for('usuarios.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Lista de Usuarios', route('usuarios.index'));
});
// Dashboard > Crear Usuario
Breadcrumbs::for('usuarios.create', function (BreadcrumbTrail $trail) {
    $trail->parent('usuarios.index');
    $trail->push('Crear Usuario', route('usuarios.create'));
});
// Dashboard > editar Usuario
Breadcrumbs::for('usuarios.edit', function (BreadcrumbTrail $trail, $user) {
    $trail->parent('usuarios.index');
    $trail->push('Editar Usuario', route('usuarios.edit', $user->id));
});

// Dashboard > ver Usuario
Breadcrumbs::for('usuarios.show', function (BreadcrumbTrail $trail, $user) {
    $trail->parent('usuarios.index');
    $trail->push('Ver Usuario', route('usuarios.show', $user->id));
});

/* --------------------------------------- */

//dashboard > accesos
Breadcrumbs::for('accesos.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Accesos', route('accesos.index'));
});

/* --------------------------------------- */
// roles y permisos
Breadcrumbs::for('roles.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Roles y Permisos', route('roles.index'));
});
// crear rol
Breadcrumbs::for('roles.create', function (BreadcrumbTrail $trail) {
    $trail->parent('roles.index');
    $trail->push('Crear Rol', route('roles.create'));
});
// editar rol
Breadcrumbs::for('roles.edit', function (BreadcrumbTrail $trail, $role) {
    $trail->parent('roles.index');
    $trail->push('Editar Rol', route('roles.edit', $role->id)); 
});
// ver rol
Breadcrumbs::for('roles.show', function (BreadcrumbTrail $trail, $role) {
    $trail->parent('roles.index');
    $trail->push('Ver Rol', route('roles.show', $role->id)); 
});

/* --------------------------------------- */
// parametros listado
Breadcrumbs::for('parametros.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Parámetros del sistema', route('parametros.index'));
});
// editar parametro
Breadcrumbs::for('parametros.edit', function (BreadcrumbTrail $trail, $parametro) {
    $trail->parent('parametros.index');
    $trail->push('Editar Parámetro', route('parametros.edit', $parametro->id)); 
});

/* --------------------------------------- */
// tipos de plaza listado
Breadcrumbs::for('tipos-plaza.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Tipos de plaza', route('tipos-plaza.index')); 
});
// editar tipo de plaza
Breadcrumbs::for('tipos-plaza.edit', function (BreadcrumbTrail $trail, $tipoPlaza) {
    $trail->parent('tipos-plaza.index');
    $trail->push('Editar tipo de plaza', route('tipos-plaza.edit', $tipoPlaza->id)); 
});

/* --------------------------------------- */
// horarios de operación listado
Breadcrumbs::for('horarios-operacion.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Horarios de operación', route('horarios-operacion.index')); 
});
// editar horario de operación
Breadcrumbs::for('horarios-operacion.edit', function (BreadcrumbTrail $trail, $horarioOperacion) {
    $trail->parent('horarios-operacion.index');     
    $trail->push('Editar horario de operación', route('horarios-operacion.edit', $horarioOperacion->id)); 
});

/* --------------------------------------- */
//dias feriados listado
Breadcrumbs::for('dias-feriado.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Días feriado', route('dias-feriado.index'));  
});
// editar dia feriado
Breadcrumbs::for('dias-feriado.edit', function (BreadcrumbTrail $trail, $diaFeriado) {
    $trail->parent('dias-feriado.index');
    $trail->push('Editar día feriado', route('dias-feriado.edit', $diaFeriado->id)); 
});
// crear dia feriado
Breadcrumbs::for('dias-feriado.create', function (BreadcrumbTrail $trail) {
    $trail->parent('dias-feriado.index');
    $trail->push('Crear día feriado', route('dias-feriado.create')); 
});

/* --------------------------------------- */
// tarifas listado
Breadcrumbs::for('tarifas.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Tarifas', route('tarifas.index')); 
}); 
// editar tarifa
Breadcrumbs::for('tarifas.edit', function (BreadcrumbTrail $trail, $tarifa) {
    $trail->parent('tarifas.index');        
    $trail->push('Editar tarifa', route('tarifas.edit', $tarifa->id)); 
});

/* --------------------------------------- */
// zonas listado
Breadcrumbs::for('zonas.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Zonas tarifadas', route('zonas.index')); 
});
// editar zona
Breadcrumbs::for('zonas.edit', function (BreadcrumbTrail $trail, $zona) {
    $trail->parent('zonas.index');      
    $trail->push('Editar zona', route('zonas.edit', $zona->id)); 
});
// crear zona  
Breadcrumbs::for('zonas.create', function (BreadcrumbTrail $trail) {
    $trail->parent('zonas.index');      
    $trail->push('Crear zona', route('zonas.create')); 
});

/* --------------------------------------- */
// calles listado
Breadcrumbs::for('calles.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Calles', route('calles.index'));
});
// crear calle
Breadcrumbs::for('calles.create', function (BreadcrumbTrail $trail) {
    $trail->parent('calles.index');
    $trail->push('Crear calle', route('calles.create'));
});
// editar calle     
Breadcrumbs::for('calles.edit', function (BreadcrumbTrail $trail, $calle) {
    $trail->parent('calles.index');
    $trail->push('Editar calle', route('calles.edit', $calle->id));
});
/* --------------------------------------- */
// manzanas listado
Breadcrumbs::for('manzanas.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Manzanas', route('manzanas.index'));
});
// crear manzana
Breadcrumbs::for('manzanas.create', function (BreadcrumbTrail $trail) {
    $trail->parent('manzanas.index');   
    $trail->push('Crear manzana', route('manzanas.create'));
});
// editar manzana
Breadcrumbs::for('manzanas.edit', function (BreadcrumbTrail $trail, $manzana) {
    $trail->parent('manzanas.index');   
    $trail->push('Editar manzana', route('manzanas.edit', $manzana->id));
});

/* --------------------------------------- */
// plazas listado
Breadcrumbs::for('plazas.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Plazas', route('plazas.index'));
});
// crear plaza
Breadcrumbs::for('plazas.create', function (BreadcrumbTrail $trail) {   
    $trail->parent('plazas.index');
    $trail->push('Crear plaza', route('plazas.create'));
});
// editar plaza 
Breadcrumbs::for('plazas.edit', function (BreadcrumbTrail $trail, $plaza) {   
    $trail->parent('plazas.index');
    $trail->push('Editar plaza', route('plazas.edit', $plaza->id));
});

/* --------------------------------------- */
// Solicitudes de agentes
Breadcrumbs::for('solicitudes-agente.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Solicitudes de agentes', route('solicitudes-agente.index'));
});
// ver solicitud de agente
Breadcrumbs::for('solicitudes-agente.show', function (BreadcrumbTrail $trail, $solicitudAgente) {
    $trail->parent('solicitudes-agente.index');
    $trail->push('Ver solicitud de agente', route('solicitudes-agente.show', $solicitudAgente->id));
});
// editar solicitud de agente
Breadcrumbs::for('solicitudes-agente.edit', function (BreadcrumbTrail $trail, $solicitudAgente) {
    $trail->parent('solicitudes-agente.index');
    $trail->push('Editar solicitud de agente', route('solicitudes-agente.edit', $solicitudAgente->id));
});

/* --------------------------------------- */
/* Cursos de capacitación */
Breadcrumbs::for('cursos-capacitacion.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Cursos de capacitación', route('cursos-capacitacion.index'));
});
// crear curso de capacitación
Breadcrumbs::for('cursos-capacitacion.create', function (BreadcrumbTrail $trail) {
    $trail->parent('cursos-capacitacion.index');
    $trail->push('Crear curso de capacitación', route('cursos-capacitacion.create'));
});
// editar curso de capacitación
Breadcrumbs::for('cursos-capacitacion.edit', function (BreadcrumbTrail $trail, $cursoCapacitacion) {
    $trail->parent('cursos-capacitacion.index');
    $trail->push('Editar curso de capacitación', route('cursos-capacitacion.edit', $cursoCapacitacion->id));
});
// ver curso de capacitación
Breadcrumbs::for('cursos-capacitacion.show', function (BreadcrumbTrail $trail, $cursoCapacitacion) {
    $trail->parent('cursos-capacitacion.index');    
    $trail->push('Ver curso de capacitación', route('cursos-capacitacion.show', $cursoCapacitacion->id));
});

/* --------------------------------------- */
/* Solicitud punto de venta */
Breadcrumbs::for('solicitudes-punto-venta.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Solicitudes de punto de venta', route('solicitudes-punto-venta.index'));
});
// ver solicitud de punto de venta
Breadcrumbs::for('solicitudes-punto-venta.show', function (BreadcrumbTrail $trail, $solicitudPuntoVenta) {
    $trail->parent('solicitudes-punto-venta.index');
    $trail->push('Ver solicitud de punto de venta', route('solicitudes-punto-venta.show', $solicitudPuntoVenta->id));
});
// editar solicitud de punto de venta
Breadcrumbs::for('solicitudes-punto-venta.edit', function (BreadcrumbTrail $trail, $solicitudPuntoVenta) {
    $trail->parent('solicitudes-punto-venta.index');
    $trail->push('Editar solicitud de punto de venta', route    ('solicitudes-punto-venta.edit', $solicitudPuntoVenta->id));
});
// crear solicitud de punto de venta
Breadcrumbs::for('solicitudes-punto-venta.create', function (BreadcrumbTrail $trail) {
    $trail->parent('solicitudes-punto-venta.index');
    $trail->push('Crear solicitud de punto de venta', route('solicitudes-punto-venta.create'));
});

