# SaaS de Gestión de Inventario Multi-Tenant (Laravel 11 + Alpine.js)

Plataforma SaaS Multi-Tenant de control de inventario y kardex construida con **Laravel 11**, **Blade**, **Tailwind CSS** y **Alpine.js**. Implementa aislamiento estricto de datos en una única base de datos (*Single-Database Multi-Tenancy*) mediante `Global Scopes` nativos de Eloquent, cálculo dinámico de stock en SQL sin columnas estáticas y reportes analíticos de rotación.

## 🚀 Enlaces de Entrega

- **URL de Producción:** `https://inventario.claudio-leiva.com` *(Reemplazar tras desplegar)*
- **Documentación del Flujo con IA:** Ver archivo [`ai-workflow.md`](./ai-workflow.md) en la raíz del proyecto.

## 🔑 Credenciales de Prueba (Incluyen Acceso en 1 Clic en `/login`)

Todos los usuarios de prueba utilizan la contraseña: **`password`**

| Empresa (Tenant) | Rubro | Rol | Correo Electrónico | Permisos |
| :--- | :--- | :--- | :--- | :--- |
| **Ferretería Industrial El Martillo S.A.** | Ferretería y Construcción | `Admin` | `admin@elmartillo.cl` | Gestión total de catálogo, kardex y creación de usuarios |
| **Ferretería Industrial El Martillo S.A.** | Ferretería y Construcción | `Staff` | `bodega@elmartillo.cl` | Consulta de catálogo y registro de entradas/salidas |
| **Farmacia & Droguería BioSalud Ltda.** | Salud y Farmacia | `Admin` | `admin@biosalud.cl` | Gestión total de catálogo, kardex y creación de usuarios |
| **Farmacia & Droguería BioSalud Ltda.** | Salud y Farmacia | `Staff` | `dispensario@biosalud.cl` | Consulta de catálogo y registro de entradas/salidas |

## 🏗️ Decisiones Arquitectónicas Destacadas

1. **Aislamiento Multi-Tenant Nativo (Sin paquetes externos):**
   - `App\Support\TenantContext`: Servicio registrado como `scoped` en el contenedor de IoC que mantiene el `tenant_id` activo por petición HTTP sin causar recursión infinita sobre el modelo `User`.
   - `App\Models\Scopes\TenantScope` y `App\Models\Concerns\BelongsToTenant`: Filtran automáticamente todas las consultas Eloquent (`$model->qualifyColumn('tenant_id')`), inyectan `tenant_id` en el evento `creating` y bloquean mutaciones cruzadas en `updating`.
2. **Stock 100% Dinámico libre de $N+1$:**
   - La tabla `products` no posee ninguna columna estática de stock.
   - El scope `Product::withCalculatedStock()` inyecta una subconsulta correlacionada con `COALESCE(SUM(CASE WHEN type = 'in' THEN quantity ELSE -quantity END), 0)` para resolver el stock actual de todo el catálogo en una única consulta SQL.
   - Al registrar salidas de inventario (`StockMovementController`), se utiliza `DB::transaction()` con bloqueo pesimista (`lockForUpdate()`) para impedir condiciones de carrera o saldos negativos.
3. **Consulta Eloquent Compleja (Top 5 Mayor Rotación en 30 días):**
   - Implementada en `Product::scopeTopRotatingLastMonth()`, combinando agregaciones condicionales (`units_out`, `units_in`, `total_rotated_units` y `movements_count`) junto con el stock calculado actual.

## 🛠️ Instalación Local

### Requisitos
- PHP >= 8.2
- Composer
- Node.js >= 18 & NPM
- SQLite, MySQL o PostgreSQL

### Pasos de Instalación

```bash
# 1. Clonar el repositorio
git clone https://github.com/tu-usuario/saas-inventario-multitenant.git
cd saas-inventario-multitenant

# 2. Instalar dependencias de PHP y Frontend
composer install
npm install

# 3. Configurar variables de entorno y llave de aplicación
cp .env.example .env
php artisan key:generate

# 4. Ejecutar migraciones y poblar los 2 Tenants (Ferretería y Farmacia)
php artisan migrate:fresh --seed

# 5. Compilar assets y levantar el servidor
npm run build
php artisan serve
