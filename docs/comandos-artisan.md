# Comandos artisan — referencia completa

> Regla de oro: **nunca crear archivos uno por uno**. Combinar flags de `make:model` para generar todo el andamiaje de una entidad en el menor número de comandos posibles.

---

## Flags estándar de `make:model`

| Flag | Genera |
|---|---|
| `-m` | Migration |
| `-c` | Controller |
| `-r` | Resource Controller (con vistas: index, create, store, show, edit, update, destroy) |
| `--api` | API Resource Controller (sin create/edit) |
| `-f` | Factory |
| `-s` | Seeder |
| `-p` | Policy |
| `--requests` | StoreRequest + UpdateRequest |
| `--all` o `-a` | Todo lo anterior (migration, factory, seeder, policy, resource controller, form requests) |

---

## Comandos estándar a usar SIEMPRE

### Para entidades del Backoffice Web (con vistas Blade)

```bash
php artisan make:model NombreEntidad -mcrfs --requests --policy
```

Genera: Modelo + Migración + Controller Resource + Factory + Seeder + StoreRequest + UpdateRequest + Policy → **8 archivos en un solo comando**.

### Para entidades de la API móvil (sin vistas)

```bash
php artisan make:model NombreEntidad -mfs --api --requests --policy
php artisan make:resource NombreEntidadResource
```

Genera: Modelo + Migración + Controller API + Factory + Seeder + StoreRequest + UpdateRequest + Policy + API Resource → **9 archivos en 2 comandos**.

---

## Comandos complementarios (cuando aplique)

```bash
# Observer del modelo
php artisan make:observer NombreObserver --model=NombreEntidad

# Resource Collection
php artisan make:resource NombreCollection --collection

# Test del controlador
php artisan make:test NombreControllerTest

# Middleware personalizado
php artisan make:middleware NombreMiddleware

# Notification
php artisan make:notification NombreNotificacion

# Job
php artisan make:job NombreJob

# Event + Listener
php artisan make:event NombreEvento
php artisan make:listener NombreListener --event=NombreEvento
```

---

## Services y Actions (carpetas personalizadas)

Laravel no tiene comandos nativos. Se crean a mano:

```bash
mkdir -p app/Services app/Actions
touch app/Services/NombreService.php
touch app/Actions/NombreAction.php
```

---

## Formato de presentación

Agrupar **todos los comandos de la fase en un solo bloque bash** con comentarios:

```bash
# 1. Generar entidad completa Zona (Backoffice)
php artisan make:model Zona -mcrfs --requests --policy

# 2. Generar Observer
php artisan make:observer ZonaObserver --model=Zona

# 3. Generar test del controlador
php artisan make:test ZonaControllerTest

# 4. Ejecutar migración
php artisan migrate

# 5. Ejecutar seeder específico
php artisan db:seed --class=ZonaSeeder
```

---

## Lo que NO quiero

- ❌ `php artisan make:migration` y luego `make:model` y luego `make:controller` por separado.
- ❌ Crear el form request en un comando aparte si pude incluirlo con `--requests`.
- ❌ Generar el policy después si pude incluirlo con `--policy`.

## Lo que SÍ quiero

- ✅ Un único `php artisan make:model` con todos los flags posibles.
- ✅ Comandos complementarios (observer, resource, test) agrupados en el mismo bloque.
- ✅ Comentarios explicando qué genera cada comando.

---

## Comandos de mantenimiento frecuentes

```bash
# Limpiar caches después de cambios en rutas/vistas/permisos
php artisan view:clear && php artisan route:clear && php artisan permission:cache-reset

# Fresh + seed (entorno de desarrollo)
php artisan migrate:fresh --seed

# Tests filtrados
php artisan test --filter=NombreTest

# Link al storage público (uploads)
php artisan storage:link
```